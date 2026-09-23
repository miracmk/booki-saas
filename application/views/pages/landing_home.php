<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="BooKi; randevu alımı, salon takvimi, WhatsApp onay ve hatırlatmaları, adisyon ve müşteri yönetimi sunan %0 komisyonlu B2B işletme platformudur.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('/') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e(vars('page_title')) ?>">
    <meta property="og:description" content="İşletmeniz için online randevu, müşteri ve takvim yönetimi tek panelde. %0 komisyonlu yeni nesil randevu altyapısı.">
    <meta property="og:url" content="<?= base_url('/') ?>">
    <meta property="og:image" content="<?= asset_url('assets/img/social-card.png') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e(vars('page_title')) ?>">
    <meta name="twitter:description" content="İşletmeniz için online randevu, müşteri ve takvim yönetimi tek panelde. %0 komisyonlu yeni nesil randevu altyapısı.">
    <meta name="twitter:image" content="<?= asset_url('assets/img/social-card.png') ?>">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "BooKi",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "All",
      "offers": {
        "@type": "AggregateOffer",
        "priceCurrency": "TRY",
        "lowPrice": "0",
        "highPrice": "1990",
        "offerCount": "4"
      },
      "description": "Online randevu ve salon yönetim sistemi. Takvim, müşteri yönetimi, adisyon, POS, e-fatura ve WhatsApp entegrasyonu."
    }
    </script>
    <link rel="icon" type="image/png" href="<?= asset_url('img/logo-16x16.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1b5e64;
            --primary-soft: #d8e9ea;
            --navy: #0a1724;
            --bg: #f7f9f9;
            --card: #ffffff;
            --text: #22303c;
            --muted: #5b6b78;
            --accent: #e8d5b0;
            --radius: 16px;
            --shadow: 0 4px 24px rgba(10, 23, 36, .08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .container { max-width: 1120px; margin: 0 auto; padding: 0 1.5rem; }

        /* ---------- Header ---------- */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e6ebee;
        }
        .site-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 68px;
        }
        .brand { display: flex; align-items: center; gap: .65rem; }
        .brand__logo {
            background: var(--navy);
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: .04em;
            border-radius: 10px;
            padding: .35rem .75rem;
            display: inline-block;
        }
        .brand__name { font-size: 1.3rem; font-weight: 800; color: var(--navy); }
        .nav-links { display: flex; gap: 1.75rem; align-items: center; }
        .nav-links a:not(.btn) { color: var(--text); font-weight: 600; font-size: .95rem; }
        .nav-links a:not(.btn):hover { color: var(--primary); text-decoration: none; }

        /* ---------- Buttons ---------- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .72rem 1.45rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: .95rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .btn:hover { transform: translateY(-1px); text-decoration: none; }
        .btn--primary { background: var(--primary); color: #fff; box-shadow: 0 6px 18px rgba(27, 94, 100, .28); }
        .btn--primary:hover { background: #155056; }
        .btn--outline { border-color: var(--primary); color: var(--primary); background: transparent; }
        .btn--light { background: #fff; color: var(--navy); }
        .btn--ghost { border-color: rgba(255, 255, 255, .5); color: #fff; }
        .btn--sm { padding: .5rem 1rem; font-size: .86rem; }

        /* ---------- Hero ---------- */
        .hero {
            background:
                radial-gradient(1200px 500px at 85% -10%, rgba(27, 94, 100, .35), transparent 60%),
                linear-gradient(160deg, #0a1724 0%, #10263a 55%, #14313d 100%);
            color: #fff;
            overflow: hidden;
        }
        .hero .container {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 3rem;
            align-items: center;
            padding-top: 5rem;
            padding-bottom: 5.5rem;
        }
        .hero__kicker {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .18);
            color: var(--accent);
            font-weight: 600;
            font-size: .82rem;
            letter-spacing: .06em;
            padding: .4rem .9rem;
            border-radius: 999px;
            margin-bottom: 1.4rem;
        }
        .hero h1 {
            font-family: "DM Serif Display", Georgia, serif;
            font-weight: 400;
            font-size: clamp(2.1rem, 4.2vw, 3.3rem);
            line-height: 1.12;
            margin-bottom: 1.2rem;
            color: #fff;
        }
        .hero h1 em { font-style: normal; color: var(--accent); }
        .hero p.lead {
            font-family: "DM Sans", sans-serif;
            font-size: 1.08rem;
            color: rgba(255, 255, 255, .82);
            max-width: 34rem;
            margin-bottom: 2rem;
        }
        .hero__actions { display: flex; flex-wrap: wrap; gap: .9rem; align-items: center; }
        .hero__proof { margin-top: 2.2rem; color: rgba(255, 255, 255, .55); font-size: .9rem; }
        .hero__proof strong { color: #fff; }

        /* Hero visual */
        .hero__visual { position: relative; }
        .browser-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 30px 60px rgba(0, 0, 0, .35);
            overflow: hidden;
        }
        .browser-card__bar {
            background: #f0f3f5;
            padding: .6rem .9rem;
            display: flex;
            gap: .4rem;
            align-items: center;
            border-bottom: 1px solid #e3e8ec;
        }
        .browser-card__bar i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .browser-card__bar i:nth-child(1) { background: #ff5f57; }
        .browser-card__bar i:nth-child(2) { background: #febc2e; }
        .browser-card__bar i:nth-child(3) { background: #28c840; }
        .browser-card__body { padding: 1.1rem; display: grid; gap: .7rem; }
        .mini-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .8rem;
            align-items: center;
            background: #f7f9f9;
            border-radius: 10px;
            padding: .65rem .8rem;
            border: 1px solid #e6ebee;
        }
        .mini-row .mini-row__title { font-size: .82rem; font-weight: 700; color: var(--text); }
        .mini-row .mini-row__sub { font-size: .76rem; color: var(--muted); }
        .mini-row__status {
            font-size: .72rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-soft);
            padding: .25rem .6rem;
            border-radius: 999px;
        }
        .mini-chip { display: inline-block; background: var(--primary); color: #fff; font-size: .72rem; font-weight: 700; padding: .22rem .55rem; border-radius: 6px; }
        .float-badge {
            position: absolute;
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: .7rem .95rem;
            font-size: .82rem;
            font-weight: 700;
            color: var(--navy);
        }
        .float-badge--wa { top: -1rem; right: -1rem; }
        .float-badge--avg { bottom: 3rem; left: -1.4rem; display: flex; gap: .6rem; align-items: center; }
        .float-badge__stars { color: #f2b01e; letter-spacing: .1em; }

        /* ---------- Trust / stats ---------- */
        .stats {
            background: #fff;
            border-bottom: 1px solid #e6ebee;
            padding: 2.4rem 0;
        }
        .stats .container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; text-align: center; }
        .stat__num { font-size: 2rem; font-weight: 800; color: var(--navy); }
        .stat__label { color: var(--muted); font-size: .9rem; }

        /* ---------- Sections ---------- */
        section { padding: 4.5rem 0; }
        .section-head { max-width: 44rem; margin-bottom: 2.8rem; }
        .section-head h2 {
            font-family: "DM Serif Display", Georgia, serif;
            font-weight: 400;
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            color: var(--navy);
            margin-bottom: .8rem;
        }
        .section-head p { color: var(--muted); font-size: 1.02rem; }

        /* Features grid */
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
        .feature-card {
            background: var(--card);
            border: 1px solid #e6ebee;
            border-radius: var(--radius);
            padding: 1.6rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .feature-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
        .feature-card__icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }
        .feature-card h3 { font-size: 1.05rem; color: var(--navy); margin-bottom: .45rem; }
        .feature-card p { color: var(--muted); font-size: .92rem; }

        /* Steps */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; counter-reset: step; }
        .step { position: relative; background: var(--card); border: 1px solid #e6ebee; border-radius: var(--radius); padding: 1.8rem 1.6rem; }
        .step::before {
            counter-increment: step;
            content: "0" counter(step);
            position: absolute;
            top: 1.2rem; right: 1.4rem;
            font-size: 2.6rem;
            font-weight: 800;
            color: #e4eaec;
        }
        .step h3 { font-size: 1.08rem; color: var(--navy); margin-bottom: .5rem; }
        .step p { color: var(--muted); font-size: .94rem; }

        /* Marketplace strip & Growth showcase */
        .marketplace { background: #fff; border-top: 1px solid #e6ebee; border-bottom: 1px solid #e6ebee; }
        .marketplace-head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        .tenant-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
        .tenant-card { background: var(--bg); border: 1px solid #e6ebee; border-radius: var(--radius); padding: 1.4rem; display: flex; flex-direction: column; justify-content: space-between; }
        .tenant-card h3 { font-size: 1.05rem; color: var(--navy); }
        .tenant-card .tenant-card__meta { color: var(--muted); font-size: .86rem; margin: .3rem 0 .8rem; }
        .tenant-card .tenant-card__stars { color: #f2b01e; font-size: .9rem; margin-bottom: 0.5rem; }
        .tenant-card .btn { margin-top: .5rem; }

        .mp-growth { background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%); border-top: 1px solid #ccfbf1; border-bottom: 1px solid #e6ebee; padding: 5rem 0; }
        .mp-growth-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2rem; margin-top: 2.5rem; }
        .mp-growth-card { background: #fff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 1.6rem; box-shadow: 0 4px 16px rgba(15, 118, 110, 0.05); transition: transform .2s, box-shadow .2s; }
        .mp-growth-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(15, 118, 110, 0.12); }
        .mp-icon-wrap { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.2rem; }
        .mp-growth-card h4 { font-size: 1.08rem; color: var(--navy); margin-bottom: 0.5rem; }
        .mp-growth-card p { font-size: 0.88rem; color: var(--muted); line-height: 1.55; }

        .category-chips-bar { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 1.5rem; }
        .category-chip { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 999px; font-size: 0.86rem; font-weight: 600; color: var(--navy); transition: all 0.2s; text-decoration: none; }
        .category-chip:hover { border-color: var(--primary); background: #f0fdfa; color: var(--primary); transform: translateY(-2px); text-decoration: none; }

        .mp-compare-box { margin-top: 3.5rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); }
        .mp-compare-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .mp-compare-table th { padding: 0.9rem 1rem; background: #f8fafc; color: var(--navy); font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        .mp-compare-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; color: var(--text); }
        .mp-compare-table tr:last-child td { border-bottom: none; }
        .badge-check { display: inline-flex; align-items: center; gap: 0.3rem; color: #0d9488; font-weight: 700; }
        .badge-cross { display: inline-flex; align-items: center; gap: 0.3rem; color: #94a3b8; }

        /* CTA */
        .cta {
            background:
                radial-gradient(900px 400px at 20% 0%, rgba(255, 255, 255, .12), transparent 55%),
                linear-gradient(160deg, #1b5e64 0%, #0a1724 120%);
            color: #fff;
            text-align: center;
        }
        .cta h2 { font-family: "DM Serif Display", Georgia, serif; font-weight: 400; font-size: clamp(1.8rem, 3.2vw, 2.6rem); margin-bottom: 1rem; }
        .cta p { color: rgba(255, 255, 255, .8); max-width: 36rem; margin: 0 auto 2rem; }

        /* Footer */
        .site-footer { background: var(--navy); color: rgba(255, 255, 255, .7); padding: 3rem 0 2rem; }
        .site-footer .container { display: flex; justify-content: space-between; gap: 2rem; flex-wrap: wrap; }
        .site-footer h4 { color: #fff; font-size: 1rem; margin-bottom: .8rem; }
        .site-footer ul { list-style: none; }
        .site-footer li { margin-bottom: .45rem; }
        .site-footer a { color: rgba(255, 255, 255, .7); font-size: .92rem; }
        .site-footer a:hover { color: #fff; text-decoration: none; }
        .site-footer__bottom {
            border-top: 1px solid rgba(255, 255, 255, .12);
            margin-top: 2.4rem;
            padding-top: 1.4rem;
            text-align: center;
            font-size: .85rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.6rem;
            cursor: pointer;
            color: var(--navy);
            line-height: 1;
            padding: 0.25rem 0.5rem;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 880px) {
            .hero .container { grid-template-columns: 1fr; padding-top: 3rem; }
            .features-grid, .steps, .tenant-cards, .stats .container, .mp-growth-cards { grid-template-columns: 1fr; }
            .mp-compare-box { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .mp-compare-table { min-width: 580px; }
            .mobile-menu-btn { display: block; }
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #ffffff;
                padding: 1.5rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                gap: 1rem;
                align-items: stretch;
                text-align: center;
            }
            .nav-links.active { display: flex; }
            .float-badge--avg { left: -0.3rem; }
        }

        /* ---------- Tenant Login Modal ---------- */
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
            background: rgba(10, 23, 36, 0.65);
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
            transition: color 0.15s;
        }
        .tenant-login-close:hover { color: var(--navy); }
        .tenant-modal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 700;
            background: var(--primary-soft);
            color: var(--primary);
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 0.8rem;
        }
        .tenant-modal-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 0.4rem;
        }
        .tenant-modal-desc {
            font-size: 0.88rem;
            color: var(--muted);
            margin-bottom: 1.4rem;
            line-height: 1.45;
        }
        .tenant-input-group label {
            display: block;
            font-size: 0.83rem;
            font-weight: 700;
            color: var(--navy);
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
            border: 1.5px solid #d8e0e5;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--navy);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .tenant-input-wrap input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(27, 94, 100, 0.15);
        }
        .tenant-modal-preview {
            margin-top: 0.5rem;
            font-size: 0.78rem;
            color: var(--muted);
            background: #f8fafc;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            word-break: break-all;
        }
        .tenant-modal-preview strong {
            color: var(--primary);
        }
        .tenant-modal-btn {
            width: 100%;
            margin-top: 1.1rem;
            padding: 0.8rem;
            background: var(--primary);
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
            background: #155056;
            transform: translateY(-1px);
        }
        .tenant-modal-footer {
            margin-top: 1.2rem;
            text-align: center;
            font-size: 0.82rem;
        }
        .tenant-modal-portal-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .tenant-modal-portal-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container" style="position:relative; display:flex; justify-content:space-between; align-items:center;">
        <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi ana sayfa">
            <span class="brand__logo">BOO·KI</span>
        </a>
        <button class="mobile-menu-btn" aria-label="Menüyü Aç" onclick="document.getElementById('mainNav').classList.toggle('active')">☰</button>
        <nav id="mainNav" class="nav-links" aria-label="Ana gezinme">
            <a href="https://booki.kibusiness.co" style="font-weight: 700; color: var(--navy);">Ana Sayfa</a>
            <a href="#ozellikler" onclick="document.getElementById('mainNav').classList.remove('active')">Özellikler</a>
            <a href="#nasil-calisir" onclick="document.getElementById('mainNav').classList.remove('active')">Nasıl Çalışır</a>
            <a href="#fiyatlandirma" onclick="document.getElementById('mainNav').classList.remove('active')">Fiyatlar</a>
            <a class="btn btn--primary btn--sm" href="<?= e(vars('portal_url')) ?>" onclick="openTenantLoginModal(event)">
                <i class="fas fa-user-circle me-1"></i> İşletme Girişi
            </a>
        </nav>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <div>
                <span class="hero__kicker">✦ %0 Komisyonlu Online Randevu &amp; Salon Yönetim Sistemi</span>
                <h1>Randevularınızı yönetin, <em>müşteri deneyiminizi</em> zirveye taşıyın.</h1>
                <p class="lead">BooKi; salon takvimi, WhatsApp onay ve hatırlatmaları, adisyon, müşteri CRM ve %0 komisyonlu online randevu altyapısıyla işletmenizi 15 dakikada dijitalleştirir.</p>
                <div class="hero__actions">
                    <a class="btn btn--light" href="<?= e(vars('portal_url')) ?>">Hemen Başlayın (14 Gün Ücretsiz)</a>
                    <a class="btn btn--ghost" href="#ozellikler">Özellikleri İnceleyin ↓</a>
                </div>
                <p class="hero__proof">Aktif <strong>işletmeler</strong> · %0 Komisyon Garantisi · WhatsApp Entegrasyonu · Kurulumda Ödeme</p>
            </div>
            <div class="hero__visual">
                <div class="browser-card">
                    <div class="browser-card__bar"><i></i><i></i><i></i></div>
                    <div class="browser-card__body">
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Yarın · 14:00</div>
                                <div class="mini-row__sub">Ayşe K. — Saç Bakımı & Fön</div>
                            </div>
                            <span class="mini-row__status">WhatsApp Onaylı</span>
                        </div>
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Yarın · 15:30</div>
                                <div class="mini-row__sub">Mehmet D. — Sakal Tıraşı</div>
                            </div>
                            <span class="mini-row__status">Onaylı</span>
                        </div>
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Cumartesi · 11:00</div>
                                <div class="mini-row__sub">Zeynep T. — Medikal Cilt Bakımı</div>
                            </div>
                            <span class="mini-chip">Online Ödendi</span>
                        </div>
                    </div>
                </div>
                <div class="float-badge float-badge--wa" style="top: -1.2rem; right: -1rem; border-left: 4px solid #10b981;">💬 WhatsApp Bildirimi: Randevu Onaylandı</div>
                <div class="float-badge float-badge--avg">
                    <span class="float-badge__stars">★★★★★</span>
                    <span>4.9 memnuniyet</span>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="stats" aria-hidden="true">
        <div class="container">
            <div><div class="stat__num">7/24</div><div class="stat__label">Online Randevu Kabulü</div></div>
            <div><div class="stat__num">%0</div><div class="stat__label">Randevu Komisyonu</div></div>
            <div><div class="stat__num">15 Dk</div><div class="stat__label">Kurulum ve Başlangıç</div></div>
            <div><div class="stat__num">%40</div><div class="stat__label">Daha Az No-Show Oranı</div></div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="ozellikler">
        <div class="container">
            <div class="section-head">
                <h2>İşletmenizi büyüten özellikler</h2>
                <p>Takvimden ödemeye, WhatsApp'tan pazarlamaya — BooKi'nin her özelliği gerçek işletme operasyonunu düşünerek tasarlandı.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-card__icon">🗓</div>
                    <h3>Online Randevu</h3>
                    <p>Müşterileriniz web sitenizden veya BooKi üzerinden 7/24 randevu alsın; saat çakışması otomatik engellensin.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">🔄</div>
                    <h3>Tekrarlanan Randevular</h3>
                    <p>Haftalık/aylık seri randevuları tek seferde planlayın; rutin bakım ve seansların takibi otomatik olsun.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">✉️</div>
                    <h3>Bekleme Listesi</h3>
                    <p>Dolu slotlar için bekleme listesi; boşalınca müşteri otomatik bilgilendirilsin, boş kalan slot dolmasın.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">💬</div>
                    <h3>WhatsApp Entegrasyonu</h3>
                    <p>Randevu hatırlatması, onayı ve pazarlama mesajları doğrudan WhatsApp üzerinden gitsin.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">🎟</div>
                    <h3>Üyelik &amp; Paketler</h3>
                    <p>Abonelik planları ve paket satışları oluşturun; kullanım hakları otomatik takip edilsin.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">🧾</div>
                    <h3>Fatura &amp; POS</h3>
                    <p>Randevu, paket ve ürün satışlarını tek faturada toplayın; ödemeleri işletme bazında takip edin.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">🤖</div>
                    <h3>Yapay Zeka Asistanı</h3>
                    <p>Randevu alma, soruları yanıtlama ve gelen mesajları yönlendirmede işletmenize destek.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">📊</div>
                    <h3>Gelir &amp; Doluluk Raporları</h3>
                    <p>Günlük/haftalık gelir, doluluk oranı ve müşteri sadakati raporlarını tek ekranda görün.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">⭐</div>
                    <h3>Puan &amp; Yorumlar</h3>
                    <p>Randevu sonrası otomatik değerlendirme isteyin; yorumları moderasyonla yayınlayın.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">🛍</div>
                    <h3>Marketplace Vitrini</h3>
                    <p>İşletmenizi BooKi marketplace'inde keşfedilebilir yapın; yeni müşteri online bulunsun.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">🔐</div>
                    <h3>KVKK / GDPR Uyumlu</h3>
                    <p>Veri indirme, hesap silme ve şifreleme ile müşteri verileriniz korunur; uyum rahatlığı sizde kalsın.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card__icon">📱</div>
                    <h3>Mobil Uyumlu</h3>
                    <p>Tüm ekranlar masaüstünden telefona sorunsuz çalışır; müşterileriniz her yerden randevu alabilir.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="nasil-calisir" style="background:#fff;border-top:1px solid #e6ebee;border-bottom:1px solid #e6ebee;">
        <div class="container">
            <div class="section-head">
                <h2>Üç adımda nasıl çalışır?</h2>
                <p>Karmaşık kurulum yok — BooKi'yi aynı gün içinde çalışır hale getirin.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <h3>Hesabınızı oluşturun</h3>
                    <p>İşletme bilgilerinizi girin, takviminizi ve çalışma saatlerinizi tanımlayın.</p>
                </div>
                <div class="step">
                    <h3>Hizmetlerinizi tanımlayın</h3>
                    <p>Hizmetlerinizi, personelinizi ve fiyatlarınızı ekleyin; randevu katmanı anında hazır olsun.</p>
                </div>
                <div class="step">
                    <h3>Linkinizi paylaşın</h3>
                    <p>Randevu linkinizi sosyal medya ve WhatsApp biyografinize ekleyin; müşterileriniz gelsin.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- B2B ONLINE REZERVASYON VE BUYUME ALTYAPISI -->
    <section class="mp-growth" id="karsilastirma">
        <div class="container">
            <div class="section-head" style="text-align: center; margin: 0 auto 3rem; max-width: 52rem;">
                <span class="hero__kicker" style="background: rgba(13, 148, 136, 0.1); border-color: rgba(13, 148, 136, 0.25); color: #0f766e;">✦ %0 KOMİSYONLU KENDİ MARKANIZLA BÜYÜME</span>
                <h2 style="font-size: clamp(1.9rem, 3.5vw, 2.7rem);">Klasik Komisyonlu Modelleri Unutun: BooKi ile Kendi Markanız Büyüsün</h2>
                <p style="font-size: 1.05rem;">Aracı pazaryerlerine her randevuda yüksek komisyon ödemek veya müşterilerinizi rakiplerinizle aynı sayfada listelemek yerine, doğrudan kendi işletme adınıza özel online randevu ve müşteri yönetimi deneyimi sunun.</p>
            </div>

            <div class="mp-growth-cards">
                <div class="mp-growth-card">
                    <div class="mp-icon-wrap" style="background: #e0f2fe; color: #0284c7;">🎯</div>
                    <h4>%0 Komisyon Garantisi</h4>
                    <p>Randevu veya masa sayınız ne kadar artarsa artsın ek komisyon ödemezsiniz. Sabit ve şeffaf paket fiyatıyla kazancınız tamamen işletmenizde kalır.</p>
                </div>
                <div class="mp-growth-card">
                    <div class="mp-icon-wrap" style="background: #dcfce7; color: #16a34a;">💬</div>
                    <h4>WhatsApp Otomatik Hatırlatma</h4>
                    <p>Randevu teyitleri, hatırlatmaları ve değişiklik bildirimleri doğrudan müşterinizin WhatsApp'ına iletilir; no-show oranı %40 azalır.</p>
                </div>
                <div class="mp-growth-card">
                    <div class="mp-icon-wrap" style="background: #fef3c7; color: #d97706;">👑</div>
                    <h4>Kendi Markanız &amp; Alan Adınız</h4>
                    <p>Müşterileriniz bir pazaryerinden değil; işletmenize özel web linki ve subdomain üzerinden doğrudan sizin takviminizden randevu alır.</p>
                </div>
                <div class="mp-growth-card">
                    <div class="mp-icon-wrap" style="background: #ede9fe; color: #7c3aed;">💳</div>
                    <h4>Adisyon, POS &amp; CRM Entegrasyonu</h4>
                    <p>Randevu, adisyon, tahsilat, e-fatura ve müşteri geçmişi tek panelde birleşir; işletme operasyonunuz saat gibi kesintisiz işler.</p>
                </div>
            </div>

            <!-- COMPARISON BOX -->
            <div class="mp-compare-box">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.3rem; color: var(--navy); margin-bottom: 0.3rem;">Neden BooKi İşletme Platformu?</h3>
                    <p style="color: var(--muted); font-size: 0.92rem;">Komisyonlu pazaryerleri ve eski nesil ajandalarla karşılaştırın, farkı kendiniz görün.</p>
                </div>
                <div style="overflow-x: auto;">
                    <table class="mp-compare-table">
                        <thead>
                            <tr>
                                <th>Kriter / Özellik</th>
                                <th style="color: #0f766e; font-weight: 800; background: #f0fdfa;">BooKi (%0 Komisyonlu SaaS)</th>
                                <th style="color: #64748b;">Komisyonlu Pazaryerleri</th>
                                <th style="color: #64748b;">Sadece Takvim / Ajanda Yazılımları</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Komisyon Oranı</strong></td>
                                <td><span class="badge-check">✓ %0 Komisyon (Sabit Paket)</span></td>
                                <td><span class="badge-cross">✕ %10 - %25 Randevu Başı Kesinti</span></td>
                                <td><span style="color:#64748b;">— Komisyonsuz</span></td>
                            </tr>
                            <tr>
                                <td><strong>Müşteri Aidiyeti</strong></td>
                                <td><span class="badge-check">✓ Müşteri Doğrudan Sizin Markanıza Gelir</span></td>
                                <td><span class="badge-cross">✕ Müşteri Pazaryerine Bağlanır &amp; Rakipleri Görür</span></td>
                                <td><span style="color:#64748b;">— Temel Takvim</span></td>
                            </tr>
                            <tr>
                                <td><strong>WhatsApp Entegrasyonu</strong></td>
                                <td><span class="badge-check">✓ Otomatik Onay &amp; Teyit Mesajları</span></td>
                                <td><span class="badge-cross">✕ Sadece SMS veya Uygulama İçi</span></td>
                                <td><span class="badge-cross">✕ Yok</span></td>
                            </tr>
                            <tr>
                                <td><strong>İşletme Yönetim Gücü</strong></td>
                                <td><span class="badge-check">✓ POS, Adisyon, WhatsApp, e-Fatura, Raporlar</span></td>
                                <td><span class="badge-cross">✕ Sadece Temel Rezervasyon</span></td>
                                <td><span style="color:#0d9488;">✓ Temel Yazılım</span></td>
                            </tr>
                            <tr>
                                <td><strong>7/24 Online Rezervasyon</strong></td>
                                <td><span class="badge-check">✓ İşletmenize Özel Sayfada Kesintisiz</span></td>
                                <td><span class="badge-check">✓ Pazaryeri Vitrininde</span></td>
                                <td><span class="badge-cross">✕ Sadece Manuel Giriş</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <a class="btn btn--primary" href="<?= e(vars('portal_url')) ?>">Hemen İşletmenizi Ekleyin &amp; 14 Gün Deneyin</a>
                    <a class="btn btn--outline" href="#fiyatlandirma" style="margin-left: 0.8rem;">Fiyatları İnceleyin →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ÖNE ÇIKAN İŞLETMELER -->
    <section class="marketplace" id="isletmeler">
        <div class="container">
            <div class="marketplace-head">
                <div class="section-head" style="margin-bottom:0;">
                    <span class="hero__kicker" style="margin-bottom: 0.5rem; display: inline-block;">✦ Başarı Hikayeleri</span>
                    <h2>BooKi ile Dijitalleşen Öncü İşletmeler</h2>
                    <p>Kuaför, güzellik salonu, klinik ve restoranlar BooKi ile randevularını ve müşteri deneyimini sorunsuz yönetiyor.</p>
                </div>
            </div>

            <?php if (empty(vars('featured_tenants'))): ?>
                <div style="background: var(--bg); border: 2px dashed #cbd5e1; border-radius: var(--radius); padding: 3rem; text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">✨</div>
                    <h3 style="color: var(--navy); font-size: 1.25rem; margin-bottom: 0.5rem;">Siz de Yerinizi Alın!</h3>
                    <p style="color: var(--muted); max-width: 32rem; margin: 0 auto 1.5rem;">İşletmenizi dakikalar içinde kaydedin, kendi online randevu sayfanızı hemen paylaşmaya başlayın.</p>
                    <a class="btn btn--primary btn--sm" href="<?= e(vars('portal_url')) ?>">İşletmenizi Kaydedin</a>
                </div>
            <?php else: ?>
                <div class="tenant-cards">
                    <?php foreach (vars('featured_tenants') as $tenant): ?>
                        <?php
                            $review_count = (int) ($tenant['review_count'] ?? 0);
                            $avg_rating = round((float) ($tenant['avg_rating'] ?? 0), 1);
                            $booking_slug = rawurlencode($tenant['subdomain']);
                            $biz_booking_url = 'https://' . $booking_slug . '-bookiapp.kibusiness.co';
                        ?>
                        <div class="tenant-card">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.4rem;">
                                    <span style="display: inline-block; background: #e0f2fe; color: #0369a1; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px;">
                                        <?= e($tenant['category'] ?? 'Hizmet & Bakım') ?>
                                    </span>
                                    <span style="color: #10b981; font-size: 0.76rem; font-weight: 700;">● Aktif</span>
                                </div>
                                <h3 style="font-size: 1.15rem; margin-bottom: 0.3rem;"><?= e($tenant['company_name'] ?? $tenant['subdomain']) ?></h3>
                                <div class="tenant-card__meta">
                                    <?= !empty($tenant['city']) ? '📍 ' . e($tenant['city']) : '📍 Türkiye' ?>
                                </div>
                                <div class="tenant-card__stars">
                                    <span style="color: #f59e0b;"><?= str_repeat('★', max(1, (int) round($avg_rating))) ?></span>
                                    <strong style="color: var(--navy); margin-left: 4px;"><?= $avg_rating > 0 ? number_format($avg_rating, 1) : '5.0' ?></strong>
                                    <span style="color: var(--muted); font-size: 0.8rem;"><?= $review_count > 0 ? ('(' . $review_count . ' değerlendirme)') : '(Onaylı İşletme)' ?></span>
                                </div>
                            </div>
                            <div style="margin-top: 1.2rem; display: flex; gap: 0.5rem;">
                                <a class="btn btn--primary btn--sm" style="flex: 1; text-align: center;" href="<?= e($biz_booking_url) ?>" target="_blank" rel="noopener">Online Randevu Al →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- PRICING -->
    <section class="pricing" id="fiyatlandirma" style="padding: 5rem 0; background: #ffffff;">
        <div class="container">
            <div class="section-head" style="text-align: center; margin-bottom: 3.5rem;">
                <span class="hero__kicker" style="display:inline-block; margin-bottom: 0.5rem;">✦ Şeffaf & Adil Paketler</span>
                <h2>İşletmenizin büyüklüğüne göre ölçeklenen planlar</h2>
                <p>İster tek kişilik stüdyo, ister çok şubeli kurumsal klinik; ihtiyacınız olan her şey hazır.</p>
            </div>
            <div class="pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
                <!-- FREE -->
                <div class="pricing-card" style="background: var(--bg); border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem; display: flex; flex-direction: column;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #e2e8f0; border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #475569;">BAŞLANGIÇ</div>
                    <h3 style="margin-top: 1rem; font-size: 1.3rem;">Free</h3>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--navy); margin: 0.75rem 0 0.25rem;">0 ₺ <span style="font-size: 0.9rem; font-weight: 500; color: var(--muted);">/ ömür boyu</span></div>
                    <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Bireysel çalışanlar ve tek kişilik işletmeler için.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem; font-size: 0.88rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem;">
                        <li>✓ 1 Personel & Takvim</li>
                        <li>✓ Sınırsız Randevu Alma</li>
                        <li>✓ Müşteri Rehberi</li>
                        <li>✓ Mobil Uyumlu Rezervasyon Sayfası</li>
                        <li style="color: #94a3b8;">✕ Raporlar & Analitik</li>
                        <li style="color: #94a3b8;">✕ WhatsApp Bildirimleri</li>
                    </ul>
                    <a class="btn btn--outline btn--sm" style="width: 100%; text-align: center;" href="<?= e(vars('portal_url')) ?>">Hemen Başla</a>
                </div>

                <!-- BASIC -->
                <div class="pricing-card" style="background: var(--bg); border: 1px solid #cbd5e1; border-radius: var(--radius); padding: 2rem; display: flex; flex-direction: column;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #e0f2fe; border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #0369a1;">TEMEL İŞLETME</div>
                    <h3 style="margin-top: 1rem; font-size: 1.3rem;">Basic</h3>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--navy); margin: 0.75rem 0 0.25rem;">490 ₺ <span style="font-size: 0.9rem; font-weight: 500; color: var(--muted);">/ ay</span></div>
                    <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Büyüyen salonlar ve operasyonunu hızlandırmak isteyenler.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem; font-size: 0.88rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem;">
                        <li>✓ 3 Personele Kadar</li>
                        <li>✓ Raporlar & Detaylı KPI Analizi</li>
                        <li>✓ Bekleme Listesi (Waitlist)</li>
                        <li>✓ Google Takvim 2-Yönlü Senkronizasyon</li>
                        <li>✓ Ürün Satışı & Stok Takibi</li>
                        <li>✓ PWA Mobil Uygulama Desteği</li>
                    </ul>
                    <a class="btn btn--primary btn--sm" style="width: 100%; text-align: center;" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Dene</a>
                </div>

                <!-- PREMIUM -->
                <div class="pricing-card" style="background: #ffffff; border: 2px solid var(--primary); border-radius: var(--radius); padding: 2rem; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(27, 94, 100, 0.12); position: relative;">
                    <div class="badge" style="position: absolute; top: -12px; right: 1.5rem; background: var(--primary); color: #fff; padding: 0.25rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.5px;">EN POPÜLER</div>
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: var(--primary-soft); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: var(--primary);">TAM PROFESYONEL</div>
                    <h3 style="margin-top: 1rem; font-size: 1.3rem;">Premium</h3>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--navy); margin: 0.75rem 0 0.25rem;">990 ₺ <span style="font-size: 0.9rem; font-weight: 500; color: var(--muted);">/ ay</span></div>
                    <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Ciro, pazarlama ve otomatik tahsilat odaklı salonlar.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem; font-size: 0.88rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem;">
                        <li>✓ 10 Personele Kadar</li>
                        <li>✓ WhatsApp Otomatik Hatırlatma & Bildirim</li>
                        <li>✓ Adisyon & POS Entegrasyonu</li>
                        <li>✓ e-Fatura (Paraşüt / QuickBooks / Zoho)</li>
                        <li>✓ Pazarlama & Akıllı Müşteri Segmentleri</li>
                        <li>✓ Çoklu Şube (Branches) Desteği</li>
                        <li>✓ Üyelikler, Seans Paketleri & Hakediş</li>
                    </ul>
                    <a class="btn btn--primary btn--sm" style="width: 100%; text-align: center; background: var(--primary);" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Dene</a>
                </div>

                <!-- ELITE -->
                <div class="pricing-card" style="background: var(--bg); border: 1px solid #cbd5e1; border-radius: var(--radius); padding: 2rem; display: flex; flex-direction: column;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #fef3c7; border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #b45309;">KURUMSAL & AI</div>
                    <h3 style="margin-top: 1rem; font-size: 1.3rem;">Elite</h3>
                    <div style="font-size: 2.2rem; font-weight: 800; color: var(--navy); margin: 0.75rem 0 0.25rem;">1.990 ₺ <span style="font-size: 0.9rem; font-weight: 500; color: var(--muted);">/ ay</span></div>
                    <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">AI asistan, özel alan adı ve tam markasız kurumsal çözüm.</p>
                    <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem; font-size: 0.88rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.6rem;">
                        <li>✓ Sınırsız Personel & Şube</li>
                        <li>✓ <strong>Çok Kanallı AI Asistan</strong> (WhatsApp/Instagram/Telegram)</li>
                        <li>✓ <strong>Özel Alan Adı (Custom Domain)</strong></li>
                        <li>✓ <strong>White-Label</strong> (Markasız Özel Arayüz)</li>
                        <li>✓ Öncelikli 7/24 Destek & Danışmanlık</li>
                        <li>✓ Özel Veri Aktarımı & Kurulum</li>
                    </ul>
                    <a class="btn btn--outline btn--sm" style="width: 100%; text-align: center;" href="<?= e(vars('portal_url')) ?>">İletişime Geçin</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>İşletmenizi dijitalleştirmeye hazır mısınız?</h2>
            <p>BooKi'yi bugün kullanmaya başlayın. Kurulum gerektirmez, dakikalar içinde randevu almaya başlayın.</p>
            <a class="btn btn--light" href="<?= e(vars('portal_url')) ?>">Hemen Başlayın</a>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <div>
            <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi">
                <span class="brand__logo">BOO·KI</span>
            </a>
            <p style="margin-top:1rem;max-width:22rem;font-size:.9rem;">
                Randevu, takvim, müşteri ve ödeme yönetimi tek panelde. Ki Software tarafından geliştirilmiştir.
            </p>
        </div>
        <div>
            <h4>Ürün</h4>
            <ul>
                <li><a href="https://booki.kibusiness.co">Ana Sayfa (booki.kibusiness.co)</a></li>
                <li><a href="#ozellikler">Özellikler</a></li>
                <li><a href="#nasil-calisir">Nasıl Çalışır</a></li>
                <li><a href="#fiyatlandirma">Fiyatlar & Paketler</a></li>
                <li><a href="<?= e(vars('portal_url')) ?>" onclick="openTenantLoginModal(event)">İşletme Girişi</a></li>
            </ul>
        </div>
        <div>
            <h4>Sektörel Çözümler</h4>
            <ul>
                <li><a href="https://booki.kibusiness.co/sektorler/kuafor-berber-randevu">Kuaför &amp; Berber Randevu</a></li>
                <li><a href="https://booki.kibusiness.co/sektorler/guzellik-salonu-randevu">Güzellik Salonu &amp; Estetik</a></li>
                <li><a href="https://booki.kibusiness.co/sektorler/klinik-doktor-randevu">Klinik &amp; Hekim Randevu</a></li>
                <li><a href="https://booki.kibusiness.co/sektorler/restoran-masa-rezervasyon">Restoran &amp; Masa Rezervasyon</a></li>
                <li><a href="https://booki.kibusiness.co/sektorler/spa-wellness-rezervasyon">Spa &amp; Masaj Rezervasyon</a></li>
            </ul>
        </div>
        <div>
            <h4>Yasal</h4>
            <ul>
                <li><a href="<?= base_url('about') ?>">Hakkımızda (About Us)</a></li>
                <li><a href="<?= base_url('privacy') ?>">Gizlilik Politikası (Privacy Policy)</a></li>
                <li><a href="<?= base_url('terms') ?>">Kullanım Şartları (Terms of Service)</a></li>
                <li><a href="<?= base_url('mesafeli-satis') ?>">Mesafeli Satış Sözleşmesi</a></li>
                <li><a href="<?= base_url('teslimat-iade') ?>">Teslimat &amp; İade</a></li>
            </ul>
        </div>
        <div>
            <h4>Şirket</h4>
            <ul>
                <li><a href="https://kibusiness.co" target="_blank" rel="noopener">Ki Business Solutions</a></li>
                <li><a href="https://kisoftware.com" target="_blank" rel="noopener">Ki Software</a></li>
            </ul>
        </div>
    </div>
    <div class="container">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,.1);margin-top:1.5rem;">
            <span style="font-size:.78rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.04em;">Güvenli Ödeme</span>
            <img src="<?= asset_url('assets/img/iyzico/footer_iyzico_ile_ode.svg') ?>" alt="iyzico ile Öde" style="height:28px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/visa.svg') ?>" alt="Visa" style="height:24px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/mastercard.svg') ?>" alt="Mastercard" style="height:26px;width:auto;" loading="lazy">
        </div>
    </div>
    <div class="site-footer__bottom">
        <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <span>© <?= date('Y') ?> BooKi — Ki Software (Ki Business Solutions). Tüm hakları saklıdır.</span>
            <span>
                <a href="<?= base_url('about') ?>" style="color:rgba(255,255,255,.8);margin-right:1rem;">Hakkımızda</a>
                <a href="<?= base_url('privacy') ?>" style="color:rgba(255,255,255,.8);margin-right:1rem;">Gizlilik / Privacy</a>
                <a href="<?= base_url('terms') ?>" style="color:rgba(255,255,255,.8);margin-right:1rem;">Şartlar / Terms</a>
                <a href="<?= base_url('mesafeli-satis') ?>" style="color:rgba(255,255,255,.8);margin-right:1rem;">Mesafeli Satış</a>
                <a href="<?= base_url('teslimat-iade') ?>" style="color:rgba(255,255,255,.8);">Teslimat &amp; İade</a>
            </span>
        </div>
    </div>
</footer>

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
            <a href="<?= e(vars('portal_url')) ?>" class="tenant-modal-portal-link">
                <i class="fas fa-search"></i> İşletme adınızı hatırlamıyor musunuz? E-posta ile bulun
            </a>
        </div>
    </div>
</div>

<script>
    const tenantAppDomain = '<?= getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co' ?>';

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
            window.location.href = '<?= e(vars('portal_url')) ?>';
        }
    }
</script>
</body>
</html>