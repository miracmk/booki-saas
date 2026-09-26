<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="BooKi; 9 ana sektör ve 168 işletme tipi için online randevu, salon takvimi, çok kanallı AI asistanı, WhatsApp bildirimleri, adisyon, POS ve e-fatura sunan %0 komisyonlu B2B işletme platformudur.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('/') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e(vars('page_title')) ?>">
    <meta property="og:description" content="9 Ana Sektör, 168 İşletme Tipi İçin %0 Komisyonlu Yeni Nesil Rezervasyon & Çok Kanallı AI İşletme Sistemi. Tüm özellikler açık!">
    <meta property="og:url" content="<?= base_url('/') ?>">
    <meta property="og:image" content="<?= asset_url('assets/img/social-card.png') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e(vars('page_title')) ?>">
    <meta name="twitter:description" content="9 Ana Sektör, 168 İşletme Tipi İçin %0 Komisyonlu Yeni Nesil Rezervasyon & Çok Kanallı AI İşletme Sistemi.">
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
        "highPrice": "4750",
        "offerCount": "5"
      },
      "description": "9 ana sektör ve 168 işletme tipi için online randevu, takvim, adisyon, POS, e-fatura, WhatsApp ve Çok Kanallı Yapay Zeka (AI) Asistanı."
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
            --primary-dark: #155056;
            --navy: #0a1724;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --accent: #e8d5b0;
            --success: #10b981;
            --success-soft: #d1fae5;
            --radius: 16px;
            --shadow: 0 4px 24px rgba(10, 23, 36, .08);
            --shadow-lg: 0 12px 32px rgba(10, 23, 36, .12);
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
        .container { max-width: 1160px; margin: 0 auto; padding: 0 1.5rem; }

        /* ---------- Header ---------- */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
        }
        .site-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 72px;
        }
        .brand { display: flex; align-items: center; gap: .65rem; }
        .brand__logo {
            background: var(--navy);
            color: #fff;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: .04em;
            border-radius: 10px;
            padding: .4rem .85rem;
            display: inline-block;
        }
        .nav-links { display: flex; gap: 1.4rem; align-items: center; }
        .nav-links a:not(.btn) { color: var(--text); font-weight: 600; font-size: .92rem; transition: color .15s; }
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
        .btn--primary:hover { background: var(--primary-dark); }
        .btn--outline { border-color: var(--primary); color: var(--primary); background: transparent; }
        .btn--outline:hover { background: var(--primary-soft); }
        .btn--light { background: #fff; color: var(--navy); }
        .btn--ghost { border-color: rgba(255, 255, 255, .5); color: #fff; }
        .btn--ghost:hover { background: rgba(255, 255, 255, .1); }
        .btn--sm { padding: .45rem 1rem; font-size: .86rem; }

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
            grid-template-columns: 1.1fr .9fr;
            gap: 3rem;
            align-items: center;
            padding-top: 5rem;
            padding-bottom: 5.5rem;
        }
        .hero__kicker {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .22);
            color: var(--accent);
            font-weight: 700;
            font-size: .82rem;
            letter-spacing: .06em;
            padding: .4rem .95rem;
            border-radius: 999px;
            margin-bottom: 1.3rem;
        }
        .hero h1 {
            font-family: "DM Serif Display", Georgia, serif;
            font-weight: 400;
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            line-height: 1.15;
            margin-bottom: 1.25rem;
            color: #fff;
        }
        .hero h1 em { font-style: normal; color: var(--accent); }
        .hero p.lead {
            font-family: "DM Sans", sans-serif;
            font-size: 1.08rem;
            color: rgba(255, 255, 255, .86);
            max-width: 36rem;
            margin-bottom: 2rem;
            line-height: 1.65;
        }
        .hero__actions { display: flex; flex-wrap: wrap; gap: .9rem; align-items: center; }
        .hero__proof { margin-top: 2rem; color: rgba(255, 255, 255, .65); font-size: .88rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
        .hero__proof span { display: inline-flex; align-items: center; gap: 0.35rem; }
        .hero__proof strong { color: #fff; }

        /* Hero visual */
        .hero__visual { position: relative; }
        .browser-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 30px 60px rgba(0, 0, 0, .4);
            overflow: hidden;
        }
        .browser-card__bar {
            background: #f1f5f9;
            padding: .7rem 1rem;
            display: flex;
            gap: .45rem;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .browser-card__bar i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .browser-card__bar i:nth-child(1) { background: #ef4444; }
        .browser-card__bar i:nth-child(2) { background: #f59e0b; }
        .browser-card__bar i:nth-child(3) { background: #10b981; }
        .browser-card__body { padding: 1.25rem; display: grid; gap: .8rem; }
        .mini-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .8rem;
            align-items: center;
            background: #f8fafc;
            border-radius: 10px;
            padding: .7rem .9rem;
            border: 1px solid #e2e8f0;
        }
        .mini-row .mini-row__title { font-size: .84rem; font-weight: 700; color: var(--navy); }
        .mini-row .mini-row__sub { font-size: .78rem; color: var(--muted); }
        .mini-row__status {
            font-size: .72rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-soft);
            padding: .25rem .6rem;
            border-radius: 999px;
        }
        .mini-chip { display: inline-block; background: #059669; color: #fff; font-size: .72rem; font-weight: 700; padding: .25rem .6rem; border-radius: 6px; }
        .float-badge {
            position: absolute;
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            padding: .75rem 1rem;
            font-size: .84rem;
            font-weight: 700;
            color: var(--navy);
        }
        .float-badge--wa { top: -1.2rem; right: -1rem; border-left: 4px solid #10b981; }
        .float-badge--avg { bottom: 2rem; left: -1.2rem; display: flex; gap: .6rem; align-items: center; }
        .float-badge__stars { color: #f59e0b; letter-spacing: .1em; }

        /* ---------- Stats ---------- */
        .stats {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 2.2rem 0;
        }
        .stats .container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; text-align: center; }
        .stat__num { font-size: 2.2rem; font-weight: 800; color: var(--navy); line-height: 1.1; }
        .stat__label { color: var(--muted); font-size: .88rem; font-weight: 600; margin-top: 0.3rem; }

        /* ---------- Section Head ---------- */
        section { padding: 5rem 0; }
        .section-head { max-width: 48rem; margin: 0 auto 3rem; text-align: center; }
        .section-head h2 {
            font-family: "DM Serif Display", Georgia, serif;
            font-weight: 400;
            font-size: clamp(1.8rem, 3.2vw, 2.6rem);
            color: var(--navy);
            margin-bottom: .8rem;
        }
        .section-head p { color: var(--muted); font-size: 1.05rem; }

        /* ---------- 9 Sectors Section ---------- */
        .sector-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            justify-content: flex-start;
            margin-bottom: 2rem;
            scrollbar-width: thin;
        }
        .sector-tab-btn {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            padding: 0.65rem 1.15rem;
            border-radius: 999px;
            font-size: 0.88rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.15s ease;
        }
        .sector-tab-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f0fdfa;
        }
        .sector-tab-btn.active {
            background: var(--navy);
            border-color: var(--navy);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(10, 23, 36, 0.15);
        }
        .sector-display-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            padding: 2.2rem;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: center;
        }
        .subsector-chips-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1.25rem;
            max-height: 240px;
            overflow-y: auto;
            padding: 0.4rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .subsector-pill {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.15s;
        }
        .subsector-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f0fdfa;
        }

        /* ---------- Omnichannel AI Section ---------- */
        .ai-section {
            background: linear-gradient(170deg, #0a1724 0%, #0d233a 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .ai-grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 3.5rem;
            align-items: center;
        }
        .ai-phone-mockup {
            background: #000;
            border: 10px solid #1e293b;
            border-radius: 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            overflow: hidden;
            max-width: 360px;
            margin: 0 auto;
        }
        .ai-phone-header {
            background: #075e54;
            color: #fff;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .ai-chat-body {
            background: #ece5dd;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            min-height: 380px;
            font-size: 0.85rem;
        }
        .chat-bubble {
            max-width: 82%;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            line-height: 1.4;
            position: relative;
        }
        .chat-bubble--user {
            background: #dcf8c6;
            color: #000;
            align-self: flex-end;
            border-top-right-radius: 2px;
        }
        .chat-bubble--bot {
            background: #ffffff;
            color: #000;
            align-self: flex-start;
            border-top-left-radius: 2px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .chat-time {
            font-size: 0.68rem;
            color: #888;
            float: right;
            margin-top: 4px;
            margin-left: 8px;
        }

        /* ---------- Comprehensive Features (6 Pillars) ---------- */
        .features-pillars-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .pillar-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            padding: 1.75rem;
            box-shadow: var(--shadow);
            transition: transform .2s ease, box-shadow .2s ease;
            display: flex;
            flex-direction: column;
        }
        .pillar-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .pillar-header {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .pillar-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .pillar-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--navy);
        }
        .module-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            flex-grow: 1;
        }
        .module-item h4 {
            font-size: 0.94rem;
            color: var(--navy);
            font-weight: 700;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .module-item p {
            font-size: 0.84rem;
            color: var(--muted);
            line-height: 1.45;
        }

        /* ---------- Steps ---------- */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; counter-reset: step; }
        .step { position: relative; background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem 1.75rem; box-shadow: var(--shadow); }
        .step::before {
            counter-increment: step;
            content: "0" counter(step);
            position: absolute;
            top: 1.2rem; right: 1.4rem;
            font-size: 2.8rem;
            font-weight: 800;
            color: #e2e8f0;
        }
        .step h3 { font-size: 1.15rem; color: var(--navy); margin-bottom: .6rem; }
        .step p { color: var(--muted); font-size: .94rem; line-height: 1.55; }

        /* ---------- Comparison & Marketplace ---------- */
        .mp-growth { background: #ffffff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .mp-compare-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); overflow-x: auto; }
        .mp-compare-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.92rem; min-width: 600px; }
        .mp-compare-table th { padding: 1rem 1.1rem; background: #f8fafc; color: var(--navy); font-weight: 800; border-bottom: 2px solid #e2e8f0; }
        .mp-compare-table td { padding: 1.05rem 1.1rem; border-bottom: 1px solid #f1f5f9; color: var(--text); }
        .badge-check { display: inline-flex; align-items: center; gap: 0.4rem; color: #0d9488; font-weight: 700; }
        .badge-cross { display: inline-flex; align-items: center; gap: 0.4rem; color: #94a3b8; }

        /* ---------- Featured Tenants ---------- */
        .marketplace { background: #f8fafc; }
        .tenant-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
        .tenant-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--shadow); }
        .tenant-card h3 { font-size: 1.1rem; color: var(--navy); }
        .tenant-card .tenant-card__meta { color: var(--muted); font-size: .88rem; margin: .3rem 0 .8rem; }
        .tenant-card .tenant-card__stars { color: #f59e0b; font-size: .92rem; margin-bottom: 0.5rem; }

        /* ---------- Pricing ---------- */
        .pricing-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        /* ---------- FAQ Accordion ---------- */
        .faq-section { background: #ffffff; border-top: 1px solid #e2e8f0; }
        .faq-list { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
        .faq-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
            transition: border-color .15s;
        }
        .faq-item.active { border-color: var(--primary); }
        .faq-question {
            width: 100%;
            padding: 1.25rem 1.5rem;
            text-align: left;
            background: none;
            border: none;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--navy);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .faq-question i {
            transition: transform .2s ease;
            color: var(--primary);
        }
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height .25s ease-out, padding .25s ease-out;
            padding: 0 1.5rem;
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .faq-item.active .faq-answer {
            max-height: 400px;
            padding: 0 1.5rem 1.25rem;
        }

        /* ---------- Contact ---------- */
        .contact-section { background: #f8fafc; border-top: 1px solid #e2e8f0; }

        /* ---------- Footer ---------- */
        .site-footer {
            background: var(--navy);
            color: #fff;
            padding: 4.5rem 0 2.5rem;
            font-size: .9rem;
        }
        .site-footer a { color: rgba(255, 255, 255, .7); }
        .site-footer a:hover { color: #fff; text-decoration: underline; }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 2.5rem;
            margin-bottom: 3rem;
        }
        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, .1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: rgba(255, 255, 255, .6);
            font-size: .85rem;
        }

        /* ---------- Mobile Menu & Responsive ---------- */
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.6rem; cursor: pointer; color: var(--navy); }
        @media (max-width: 992px) {
            .hero .container { grid-template-columns: 1fr; }
            .hero__visual { display: none; }
            .ai-grid { grid-template-columns: 1fr; }
            .features-pillars-grid { grid-template-columns: repeat(2, 1fr); }
            .sector-display-card { grid-template-columns: 1fr; }
            .stats .container { grid-template-columns: repeat(2, 1fr); }
            .tenant-cards { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .mobile-menu-btn { display: block; }
            .nav-links {
                display: none;
                position: absolute;
                top: 72px;
                left: 0;
                width: 100%;
                background: #fff;
                flex-direction: column;
                padding: 1.5rem;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                border-bottom: 1px solid #e2e8f0;
                gap: 1rem;
            }
            .nav-links.active { display: flex; }
            .features-pillars-grid { grid-template-columns: 1fr; }
            .steps { grid-template-columns: 1fr; }
            .stats .container { grid-template-columns: 1fr 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
        }

        /* Tenant Login Modal */
        .tenant-modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(10, 23, 36, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .tenant-modal-backdrop.open { display: flex; }
        .tenant-login-dialog {
            background: #fff;
            border-radius: var(--radius);
            max-width: 440px;
            width: 100%;
            padding: 2.2rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .tenant-login-close {
            position: absolute;
            top: 1rem; right: 1.2rem;
            background: none; border: none;
            font-size: 1.7rem; color: #94a3b8;
            cursor: pointer; line-height: 1;
        }
        .tenant-login-close:hover { color: var(--navy); }
        .tenant-modal-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            font-size: 0.8rem; font-weight: 700;
            background: var(--primary-soft); color: var(--primary);
            padding: 0.3rem 0.75rem; border-radius: 999px;
            margin-bottom: 0.8rem;
        }
        .tenant-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .tenant-input-wrap i { position: absolute; left: 0.9rem; color: #94a3b8; }
        .tenant-input-wrap input {
            width: 100%;
            padding: 0.7rem 0.9rem 0.7rem 2.4rem;
            border: 1.5px solid #d8e0e5;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--navy);
            outline: none;
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
        }
        .tenant-modal-btn:hover { background: var(--primary-dark); }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container">
        <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi ana sayfa">
            <span class="brand__logo">BOO·KI</span>
        </a>
        <button class="mobile-menu-btn" aria-label="Menüyü Aç" onclick="document.getElementById('mainNav').classList.toggle('active')">☰</button>
        <nav id="mainNav" class="nav-links" aria-label="Ana gezinme">
            <a href="https://booki.kibusiness.co" style="font-weight: 700; color: var(--navy);">Ana Sayfa</a>
            <a href="#sektorler" onclick="document.getElementById('mainNav').classList.remove('active')">Sektörler</a>
            <a href="#yapay-zeka" onclick="document.getElementById('mainNav').classList.remove('active')">Yapay Zeka</a>
            <a href="#ozellikler" onclick="document.getElementById('mainNav').classList.remove('active')">Özellikler</a>
            <a href="#nasil-calisir" onclick="document.getElementById('mainNav').classList.remove('active')">Nasıl Çalışır</a>
            <a href="#fiyatlandirma" onclick="document.getElementById('mainNav').classList.remove('active')">Fiyatlar</a>
            <a href="#sss" onclick="document.getElementById('mainNav').classList.remove('active')">SSS</a>
            <a href="#iletisim" onclick="document.getElementById('mainNav').classList.remove('active')">İletişim</a>
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
                <span class="hero__kicker">✦ 9 Ana Sektör · 168 İşletme Tipi · %0 Komisyon Garantisi</span>
                <h1>Randevularınızı yönetin, <em>yapay zeka</em> ile zirveye taşıyın.</h1>
                <p class="lead">BooKi; salon takvimi, WhatsApp onay ve hatırlatmaları, adisyon, POS, e-fatura ve 7/24 randevu alan Çok Kanallı AI Asistanı ile işletmenizi 15 dakikada dijitalleştirir. Tüm özellikler açık, adil kapasite baremleri!</p>
                <div class="hero__actions">
                    <a class="btn btn--light" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Başlayın</a>
                    <a class="btn btn--ghost" href="#sektorler">Sektörleri Keşfedin ↓</a>
                </div>
                <div class="hero__proof">
                    <span><i class="fas fa-check-circle" style="color:var(--success);"></i> %0 Komisyon Garantisi</span>
                    <span><i class="fas fa-check-circle" style="color:var(--success);"></i> Tüm Özellikler Açık</span>
                    <span><i class="fas fa-check-circle" style="color:var(--success);"></i> WhatsApp & AI Entegre</span>
                </div>
            </div>
            <div class="hero__visual">
                <div class="browser-card">
                    <div class="browser-card__bar"><i></i><i></i><i></i></div>
                    <div class="browser-card__body">
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Yarın · 14:00</div>
                                <div class="mini-row__sub">Ayşe K. — Saç Bakımı & Fön (Koltuk 2)</div>
                            </div>
                            <span class="mini-row__status">WhatsApp Onaylı</span>
                        </div>
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Yarın · 15:30</div>
                                <div class="mini-row__sub">Mehmet D. — Klinik Muayene / Dr. Selim</div>
                            </div>
                            <span class="mini-row__status">AI ile Alındı</span>
                        </div>
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Cumartesi · 11:00</div>
                                <div class="mini-row__sub">Zeynep T. — Medikal Cilt Bakımı (Seans 3/6)</div>
                            </div>
                            <span class="mini-chip">Online Ödendi</span>
                        </div>
                    </div>
                </div>
                <div class="float-badge float-badge--wa">
                    <i class="fab fa-whatsapp" style="color:#25d366; font-size:1.1rem; margin-right:4px;"></i>
                    WhatsApp Bildirimi: Randevu Onaylandı
                </div>
                <div class="float-badge float-badge--avg">
                    <span class="float-badge__stars">★★★★★</span>
                    <span>4.9 / 5 Memnuniyet</span>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="stats" aria-hidden="true">
        <div class="container">
            <div><div class="stat__num">9</div><div class="stat__label">Ana Sektör & 168 İşletme Tipi</div></div>
            <div><div class="stat__num">%0</div><div class="stat__label">Randevu Komisyonu (Sabit Ücret)</div></div>
            <div><div class="stat__num">7/24</div><div class="stat__label">Çok Kanallı AI Asistanı</div></div>
            <div><div class="stat__num">%40</div><div class="stat__label">Daha Az Randevu İptali (No-Show)</div></div>
        </div>
    </section>

    <!-- 9 SECTORS INTERACTIVE SECTION -->
    <section id="sektorler" style="background:#ffffff; border-bottom:1px solid #e2e8f0;">
        <div class="container">
            <div class="section-head">
                <span class="hero__kicker" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd; margin-bottom:0.6rem;">✦ Her Sektörün Dinamiği Farklıdır</span>
                <h2>9 Ana Sektör, 168 Alt İşletme Tipi İçin Özelleştirildi</h2>
                <p>İster kuaför salonu, ister restoran, ister tıp kliniği veya pilates stüdyosu olun; BooKi sektörünüzün terminolojisine ve kapasite ihtiyaçlarına tam uyum sağlar.</p>
            </div>

            <!-- Sector Tab Buttons -->
            <div class="sector-tabs" id="sector-tabs-container">
                <?php 
                $main_sectors_list = function_exists('get_main_sectors') ? get_main_sectors() : [];
                $first_sec = true;
                foreach ($main_sectors_list as $sec_title => $sec_info): 
                ?>
                    <button type="button" class="sector-tab-btn <?= $first_sec ? 'active' : '' ?>" onclick="switchSectorTab('<?= e($sec_title) ?>', this)">
                        <span><?= e($sec_info['icon'] ?? '🏢') ?></span>
                        <span><?= e($sec_title) ?></span>
                    </button>
                <?php 
                $first_sec = false;
                endforeach; 
                ?>
            </div>

            <!-- Dynamic Sector Display Card -->
            <div class="sector-display-card" id="sector-display-box">
                <div>
                    <div style="display:inline-flex; align-items:center; gap:0.4rem; background:var(--primary-soft); color:var(--primary); font-size:0.8rem; font-weight:700; padding:0.3rem 0.75rem; border-radius:999px; margin-bottom:0.75rem;">
                        <span id="active-sector-icon">💅</span> <span id="active-sector-group">Güzellik & Kişisel Bakım</span>
                    </div>
                    <h3 id="active-sector-title" style="font-size:1.6rem; color:var(--navy); margin-bottom:0.75rem; font-family:'DM Serif Display', Georgia, serif;">Güzellik Merkezleri, Kuaförler & Bakım Stüdyoları</h3>
                    <p id="active-sector-desc" style="color:var(--muted); font-size:0.95rem; line-height:1.6; margin-bottom:1.25rem;">
                        Koltuk, kabin ve uzman bazlı randevu akışı, seans ve paket satışları, WhatsApp teyit mesajları ve adisyon tek ekranda.
                    </p>
                    <div id="active-sector-features" style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:1.5rem;">
                        <div style="font-size:0.85rem; font-weight:600; color:var(--navy); display:flex; align-items:center; gap:0.4rem;">
                            <i class="fas fa-check-circle" style="color:var(--primary);"></i> Koltuk / Kabin & İstasyon Takibi
                        </div>
                        <div style="font-size:0.85rem; font-weight:600; color:var(--navy); display:flex; align-items:center; gap:0.4rem;">
                            <i class="fas fa-check-circle" style="color:var(--primary);"></i> Uzman Bazlı Çalışma & Prim
                        </div>
                        <div style="font-size:0.85rem; font-weight:600; color:var(--navy); display:flex; align-items:center; gap:0.4rem;">
                            <i class="fas fa-check-circle" style="color:var(--primary);"></i> Seans & Paket Satış Düşümü
                        </div>
                        <div style="font-size:0.85rem; font-weight:600; color:var(--navy); display:flex; align-items:center; gap:0.4rem;">
                            <i class="fas fa-check-circle" style="color:var(--primary);"></i> WhatsApp Hatırlatma & Onay
                        </div>
                    </div>
                    <div>
                        <a href="#fiyatlandirma" id="btn-goto-pricing" class="btn btn--primary btn--sm" onclick="selectSectorForPricing()">
                            Bu Sektörün Kapasite Baremlerini İncele ↓
                        </a>
                    </div>
                </div>

                <div>
                    <div style="font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                        <span>Kapsanan Alt İşletme Tipleri:</span>
                        <span id="subsector-count-badge" style="background:#e2e8f0; font-size:0.75rem; padding:0.15rem 0.5rem; border-radius:999px;">19 İşletme Tipi</span>
                    </div>
                    <div class="subsector-chips-grid" id="active-subsectors-pills">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- OMNICHANNEL AI ASSISTANT SECTION -->
    <section class="ai-section" id="yapay-zeka">
        <div class="container">
            <div class="ai-grid">
                <div>
                    <span class="hero__kicker" style="background:rgba(16, 185, 129, 0.2); color:#6ee7b7; border-color:rgba(16, 185, 129, 0.4); margin-bottom:0.75rem;">
                        🤖 7/24 Çok Kanallı AI Asistanı
                    </span>
                    <h2 style="font-family:'DM Serif Display', Georgia, serif; font-size:clamp(1.9rem, 3.4vw, 2.7rem); color:#fff; line-height:1.2; margin-bottom:1rem;">
                        Müşterileriniz WhatsApp ve Web'den Yazsın, Yapay Zeka Randevuyu Kapatsın.
                    </h2>
                    <p style="color:rgba(255,255,255,0.85); font-size:1.02rem; line-height:1.65; margin-bottom:1.75rem;">
                        Gece yarısı, tatil günlerinde veya salonun en yoğun anında bile tek bir randevu kaçırmayın. BooKi AI Asistanı; müşterinizin doğal dilini anlar, takviminizdeki boş slotları ve uygun personeli sorgular, randevuyu teyit eder ve panele anında işler.
                    </p>

                    <div style="display:flex; flex-direction:column; gap:1rem; margin-bottom:2rem;">
                        <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                            <div style="width:28px; height:28px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.85rem; margin-top:2px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <strong style="color:#fff; font-size:0.95rem;">Çok Kanallı Entegrasyon:</strong>
                                <p style="color:rgba(255,255,255,0.75); font-size:0.88rem; margin:0;">WhatsApp Cloud API, Instagram DM, Web Rezervasyon Chat ve Telegram ile eşzamanlı çalışır.</p>
                            </div>
                        </div>

                        <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                            <div style="width:28px; height:28px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.85rem; margin-top:2px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <strong style="color:#fff; font-size:0.95rem;">Doğal Dil Anlama (NLU):</strong>
                                <p style="color:rgba(255,255,255,0.75); font-size:0.88rem; margin:0;">"Yarın saat 14:00'te Ayşe Hanım'a manikür için yer var mı?" gibi gerçek insan cümlelerini anında çözümler.</p>
                            </div>
                        </div>

                        <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                            <div style="width:28px; height:28px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.85rem; margin-top:2px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <strong style="color:#fff; font-size:0.95rem;">Otomatik Hatırlatma & No-Show Önleme:</strong>
                                <p style="color:rgba(255,255,255,0.75); font-size:0.88rem; margin:0;">Randevudan 24 saat ve 2 saat önce WhatsApp'tan otomatik onay ister; iptal veya erteleme taleplerini yönetir.</p>
                            </div>
                        </div>

                        <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                            <div style="width:28px; height:28px; border-radius:50%; background:#f59e0b; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.85rem; margin-top:2px;">
                                <i class="fas fa-sparkles"></i>
                            </div>
                            <div>
                                <strong style="color:#fde68a; font-size:0.95rem;">Ücretsiz Hariç Tüm Paketlerde Açık:</strong>
                                <p style="color:rgba(255,255,255,0.75); font-size:0.88rem; margin:0;">Başlangıç, Orta, Premium ve Özel paketlerin tamamında AI Asistanı sınırsız olarak dahil edilmiştir.</p>
                            </div>
                        </div>
                    </div>

                    <a class="btn btn--light" href="<?= e(vars('portal_url')) ?>">AI Asistanı 14 Gün Ücretsiz Deneyin</a>
                </div>

                <!-- Smartphone Chat Simulation Mockup -->
                <div>
                    <div class="ai-phone-mockup">
                        <div class="ai-phone-header">
                            <div style="width:36px; height:36px; border-radius:50%; background:#25d366; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem;">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div>
                                <div style="font-weight:700; font-size:0.9rem;">BooKi AI Asistanı</div>
                                <div style="font-size:0.72rem; color:rgba(255,255,255,0.85);">● Çevrimiçi · Doğrulanmış İşletme</div>
                            </div>
                        </div>
                        <div class="ai-chat-body">
                            <div class="chat-bubble chat-bubble--user">
                                Merhaba, yarın öğleden sonra saç boyama ve fön için yeriniz var mı? Zeynep Hanım olsun lütfen.
                                <span class="chat-time">13:14</span>
                            </div>
                            <div class="chat-bubble chat-bubble--bot">
                                Merhaba Selin Hanım! 😊 Takvimimizi kontrol ettim. Yarın Zeynep Hanım'ın saat <strong>14:30</strong> ve <strong>16:45</strong> için uygunluğu bulunuyor. Hangi saat sizin için daha uygun olur?
                                <span class="chat-time">13:14</span>
                            </div>
                            <div class="chat-bubble chat-bubble--user">
                                14:30 harika olur, kaydedebilir misiniz?
                                <span class="chat-time">13:15</span>
                            </div>
                            <div class="chat-bubble chat-bubble--bot">
                                Randevunuz oluşturuldu! ✨<br>
                                🗓 <strong>Yarın (26 Eylül) 14:30</strong><br>
                                💇 <strong>Hizmet:</strong> Saç Boyama & Fön<br>
                                👤 <strong>Uzman:</strong> Zeynep Hanım<br>
                                📍 <strong>Yer:</strong> Koltuk 3<br>
                                Randevunuz takviminize işlendi. Gelmeden 2 saat önce hatırlatma ileteceğiz. Görüşmek üzere! 🌸
                                <span class="chat-time">13:15</span>
                            </div>
                            <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:0.5rem 0.75rem; font-size:0.78rem; color:#065f46; display:flex; align-items:center; gap:0.4rem;">
                                <i class="fas fa-calendar-check" style="color:#10b981;"></i>
                                <span><strong>Sistem Notu:</strong> Takvime işlendi & WhatsApp onay SMS'i gönderildi.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- COMPREHENSIVE FEATURES (24 MODULES IN 6 PILLARS) -->
    <section id="ozellikler">
        <div class="container">
            <div class="section-head">
                <span class="hero__kicker" style="background:#f1f5f9; color:#475569; border-color:#cbd5e1; margin-bottom:0.6rem;">✦ Eksiksiz İşletme İşletim Sistemi</span>
                <h2>İşletmenizin İhtiyaç Duyduğu Tüm Modüller Tek Panelde</h2>
                <p>6 ana kategoride 24 güçlü modül. Başka hiçbir ek yazılıma veya eklentiye ihtiyaç duymadan işletmenizi uçtan uca yönetin.</p>
                <div style="margin-top:1rem; display:inline-block; background:#ecfdf5; border:1px solid #bbf7d0; color:#065f46; font-size:0.85rem; font-weight:700; padding:0.45rem 1rem; border-radius:999px;">
                    ✨ Tüm özellikler tüm paketlerimizde açıktır (Tek istisna: Ücretsiz planda AI Asistan hariçtir).
                </div>
            </div>

            <div class="features-pillars-grid">
                <!-- PILLAR 1: Randevu & Akıllı Takvim -->
                <div class="pillar-card">
                    <div class="pillar-header">
                        <div class="pillar-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="pillar-title">Randevu & Takvim</div>
                            <div style="font-size:0.78rem; color:var(--muted); font-weight:600;">Otomatik Slot Yönetimi</div>
                        </div>
                    </div>
                    <ul class="module-list">
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:var(--primary); font-size:0.75rem;"></i> 7/24 Online Randevu Motoru</h4>
                            <p>Müşterileriniz web sitenizden veya sosyal medya biyografinizden dilediği an randevu alsın.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:var(--primary); font-size:0.75rem;"></i> Çakışma Önleme & Mesai Takvimi</h4>
                            <p>Çift rezervasyonları otomatik engeller, personel mola ve izin saatlerini hassasiyetle hesaplar.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:var(--primary); font-size:0.75rem;"></i> Yinelenen & Seri Randevular</h4>
                            <p>Haftalık ve aylık düzenli seansları tek tıkla planlayın, takvimi otomatik rezerve edin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:var(--primary); font-size:0.75rem;"></i> Akıllı Bekleme Listesi</h4>
                            <p>Dolu saatlerde sıraya giren müşterilere, iptal gerçekleştiğinde anında otomatik bildirim gider.</p>
                        </li>
                    </ul>
                </div>

                <!-- PILLAR 2: Çok Kanallı AI & İletişim -->
                <div class="pillar-card">
                    <div class="pillar-header">
                        <div class="pillar-icon" style="background:#ecfdf5; color:#059669;"><i class="fas fa-robot"></i></div>
                        <div>
                            <div class="pillar-title">AI & İletişim</div>
                            <div style="font-size:0.78rem; color:var(--muted); font-weight:600;">Kesintisiz Müşteri Deneyimi</div>
                        </div>
                    </div>
                    <ul class="module-list">
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#059669; font-size:0.75rem;"></i> Çok Kanallı AI Randevu Asistanı</h4>
                            <p>WhatsApp, Instagram ve Web üzerinden gelen mesajları yanıtlar ve randevuyu kapatır.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#059669; font-size:0.75rem;"></i> WhatsApp Onay & Hatırlatmalar</h4>
                            <p>Randevu oluşturulduğunda ve 24s/2s kala otomatik teyit mesajları ile no-show'u %40 azaltır.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#059669; font-size:0.75rem;"></i> SMS & E-Posta Bildirimleri</h4>
                            <p>Operatör bağımsız Netgsm ve kurumsal e-posta bildirimleri ile müşterileriniz daima bilgilensin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#059669; font-size:0.75rem;"></i> Doğrulanmış Müşteri Yorumları</h4>
                            <p>Hizmet sonrası otomatik memnuniyet anketi gönderin; Google ve portal puanlarınızı yükseltin.</p>
                        </li>
                    </ul>
                </div>

                <!-- PILLAR 3: Satış, POS & Ön Muhasebe -->
                <div class="pillar-card">
                    <div class="pillar-header">
                        <div class="pillar-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-cash-register"></i></div>
                        <div>
                            <div class="pillar-title">POS, Adisyon & Finans</div>
                            <div style="font-size:0.78rem; color:var(--muted); font-weight:600;">Eksiksiz Kasa Yönetimi</div>
                        </div>
                    </div>
                    <ul class="module-list">
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#d97706; font-size:0.75rem;"></i> Entegre POS & Çoklu Ödeme</h4>
                            <p>Nakit, Kredi Kartı, Havale/EFT ve parçalı ödemeleri tek fişte sorunsuz tahsil edin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#d97706; font-size:0.75rem;"></i> Adisyon & Masa / Koltuk Takibi</h4>
                            <p>Hizmet esnasında satılan kozmetik ürünlerini ve ek işlemleri aynı adisyonda birleştirin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#d97706; font-size:0.75rem;"></i> e-Fatura / e-Arşiv Çözümleri</h4>
                            <p>GİB uyumlu e-fatura entegrasyonu ile tahsilat anında tek tıkla dijital fatura kesin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#d97706; font-size:0.75rem;"></i> Personel Prim & Hakediş Takibi</h4>
                            <p>Hizmet veya ürün satışına göre personel bazlı komisyon ve primleri kuruşu kuruşuna hesaplayın.</p>
                        </li>
                    </ul>
                </div>

                <!-- PILLAR 4: Sektörel Dikey Çözümler -->
                <div class="pillar-card">
                    <div class="pillar-header">
                        <div class="pillar-icon" style="background:#ede9fe; color:#7c3aed;"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <div class="pillar-title">Sektörel Dikey Modüller</div>
                            <div style="font-size:0.78rem; color:var(--muted); font-weight:600;">Sektöre Özel İş Akışları</div>
                        </div>
                    </div>
                    <ul class="module-list">
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#7c3aed; font-size:0.75rem;"></i> Koltuk / Kabin & İstasyon</h4>
                            <p>Kuaför ve güzellik salonları için koltuk ve medikal kabin kapasitelerini çakışmasız yönetin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#7c3aed; font-size:0.75rem;"></i> Masa Düzeni & Restoran Rezervasyon</h4>
                            <p>Kişi sayısı, salon planı ve yemek servisi saatlerine uygun entegre masa takip modülü.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#7c3aed; font-size:0.75rem;"></i> Danışan / Hasta Anamnez Dosyaları</h4>
                            <p>Klinik ve diyetisyenler için güvenli seans geçmişi, tıbbi notlar ve şifreli dosya arşivi.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#7c3aed; font-size:0.75rem;"></i> Saha, Kort & Grup Dersi Kontenjanı</h4>
                            <p>Spor stüdyoları ve halı sahalar için kontenjan kısıtlamalı saatlik rezervasyon ve ödeme.</p>
                        </li>
                    </ul>
                </div>

                <!-- PILLAR 5: Sadakat, Paketler & Pazarlama -->
                <div class="pillar-card">
                    <div class="pillar-header">
                        <div class="pillar-icon" style="background:#fee2e2; color:#dc2626;"><i class="fas fa-gift"></i></div>
                        <div>
                            <div class="pillar-title">Paket, Sadakat & Büyüme</div>
                            <div style="font-size:0.78rem; color:var(--muted); font-weight:600;">Müşteri Sadakati & Satış</div>
                        </div>
                    </div>
                    <ul class="module-list">
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#dc2626; font-size:0.75rem;"></i> Barkodlu Ürün & Stok Takibi</h4>
                            <p>Kritik stok seviyesi uyarıları ile sarf malzeme ve perakende ürünlerinizi eksiksiz takip edin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#dc2626; font-size:0.75rem;"></i> Seans & Paket Satış Yönetimi</h4>
                            <p>6 seans lazer veya 10 ders pilates gibi paketleri satın, her randevuda kalan hakkı otomatik düşün.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#dc2626; font-size:0.75rem;"></i> Dijital Hediye Kartı & Kuponlar</h4>
                            <p>Müşterilerinize özel indirim kodları, hediye kartları ve sadakat kampanyaları tanımlayın.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#dc2626; font-size:0.75rem;"></i> %0 Komisyonlu Marketplace Vitrini</h4>
                            <p>BooKi pazaryerinde listelenin, yeni müşterileri hiçbir randevu komisyonu ödemeden kazanın.</p>
                        </li>
                    </ul>
                </div>

                <!-- PILLAR 6: Kurumsal Altyapı & Güvenlik -->
                <div class="pillar-card">
                    <div class="pillar-header">
                        <div class="pillar-icon" style="background:#e0e7ff; color:#4338ca;"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <div class="pillar-title">Kurumsal & Güvenlik</div>
                            <div style="font-size:0.78rem; color:var(--muted); font-weight:600;">Yüksek Güvenilirlik</div>
                        </div>
                    </div>
                    <ul class="module-list">
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#4338ca; font-size:0.75rem;"></i> Çoklu Şube (Branches) & Franchise</h4>
                            <p>Tüm şubelerinizin doluluk ve ciro raporlarını tek bir patron ekranından eşzamanlı izleyin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#4338ca; font-size:0.75rem;"></i> Özel Alan Adı (Custom Domain)</h4>
                            <p>randevu.sizinmarkaniz.com şeklinde kendi logonuz ve renklerinizle White-Label kullanım.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#4338ca; font-size:0.75rem;"></i> Rol Bazlı Yetkilendirme (RBAC)</h4>
                            <p>Kasa, uzman, yönetici ve resepsiyon için ekran ve veri erişim yetkilerini ayrı ayrı belirleyin.</p>
                        </li>
                        <li class="module-item">
                            <h4><i class="fas fa-check" style="color:#4338ca; font-size:0.75rem;"></i> KVKK / GDPR Uyumlu Şifreleme</h4>
                            <p>Müşteri PII verileri veritabanında şifreli saklanır; günlük otomatik yedekleme ile veriniz güvende.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="nasil-calisir" style="background:#ffffff; border-top:1px solid #e2e8f0; border-bottom:1px solid #e2e8f0;">
        <div class="container">
            <div class="section-head">
                <span class="hero__kicker" style="background:#f1f5f9; color:#475569; border-color:#cbd5e1; margin-bottom:0.6rem;">✦ Kolay & Hızlı Kurulum</span>
                <h2>15 Dakikada Nasıl Çalışır?</h2>
                <p>Karmaşık teknik kurulum yok — BooKi'yi aynı gün içinde zahmetsizce kullanmaya başlayın.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <h3>1. Hesabınızı Oluşturun</h3>
                    <p>İşletme adınızı ve sektörünüzü seçin; paneliniz, randevu sayfanız ve veritabanınız anında otomatik kurulsun.</p>
                </div>
                <div class="step">
                    <h3>2. Hizmet & Personeli Ekleyin</h3>
                    <p>Hizmetlerinizi, işlem sürelerinizi, fiyatlarınızı ve çalışan personelinizi tanımlayın; takviminiz anında hazır hale gelsin.</p>
                </div>
                <div class="step">
                    <h3>3. Linkinizi & AI'ı Paylaşın</h3>
                    <p>Randevu linkinizi Instagram biyografinize, web sitenize veya WhatsApp numaranıza bağlayın; 7/24 rezervasyon akışı başlasın.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- GROWTH & MARKETPLACE COMPARISON -->
    <section class="mp-growth">
        <div class="container">
            <div class="section-head">
                <span class="hero__kicker" style="background:#ecfdf5; color:#065f46; border-color:#a7f3d0; margin-bottom:0.6rem;">✦ Neden BooKi?</span>
                <h2>Klasik Komisyonlu Sistemler ile BooKi Karşılaştırması</h2>
                <p>Müşterinizi size ait tutun, kazancınızı komisyonlara kaptırmayın.</p>
            </div>

            <div class="mp-compare-box">
                <table class="mp-compare-table">
                    <thead>
                        <tr>
                            <th>Özellik / Kriter</th>
                            <th style="color:var(--primary); font-size:1.05rem;">✨ BooKi İşletim Sistemi</th>
                            <th>Klasik Komisyonlu Pazaryerleri</th>
                            <th>Basit Takvim Yazılımları</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Komisyon Oranı</strong></td>
                            <td><span class="badge-check">✓ %0 Komisyon (Sabit Aylık/Yıllık Paket)</span></td>
                            <td><span class="badge-cross">✕ %10 - %25 Randevu Başı Kesinti</span></td>
                            <td><span style="color:#64748b;">— Komisyonsuz</span></td>
                        </tr>
                        <tr>
                            <td><strong>Müşteri Aidiyeti</strong></td>
                            <td><span class="badge-check">✓ Müşteri Sizin Markanıza Gelir</span></td>
                            <td><span class="badge-cross">✕ Müşteri Pazaryerine Bağlanır & Rakipleri Görür</span></td>
                            <td><span style="color:#64748b;">— Temel Takvim</span></td>
                        </tr>
                        <tr>
                            <td><strong>7/24 Çok Kanallı AI Asistanı</strong></td>
                            <td><span class="badge-check">✓ WhatsApp, Web, Instagram'da Randevu Kapatır</span></td>
                            <td><span class="badge-cross">✕ Yok veya Sadece Uygulama Bildirimi</span></td>
                            <td><span class="badge-cross">✕ Yok</span></td>
                        </tr>
                        <tr>
                            <td><strong>Tüm Özellikler Açık Politikası</strong></td>
                            <td><span class="badge-check">✓ POS, Adisyon, Stok, Fatura, Raporlar Açık</span></td>
                            <td><span class="badge-cross">✕ Ekstra Ücretler & Kısıtlamalar</span></td>
                            <td><span class="badge-cross">✕ Sadece Temel Takvim</span></td>
                        </tr>
                        <tr>
                            <td><strong>Otomatik WhatsApp Hatırlatmaları</strong></td>
                            <td><span class="badge-check">✓ Otomatik 24s/2s Kala Teyit Mesajları</span></td>
                            <td><span class="badge-cross">✕ Sadece SMS veya Kısıtlı Bildirim</span></td>
                            <td><span class="badge-cross">✕ Yok</span></td>
                        </tr>
                        <tr>
                            <td><strong>Özel Alan Adı (Custom Domain)</strong></td>
                            <td><span class="badge-check">✓ randevu.markaniz.com White-Label Desteği</span></td>
                            <td><span class="badge-cross">✕ İmkansız (Sadece pazaryeri linki)</span></td>
                            <td><span class="badge-cross">✕ Yok</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="text-align:center; margin-top:2.5rem;">
                <a class="btn btn--primary" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Başlayın & %0 Komisyonu Yaşayın</a>
                <a class="btn btn--outline" href="#fiyatlandirma" style="margin-left:0.8rem;">Paket ve Fiyatları İnceleyin →</a>
            </div>
        </div>
    </section>

    <!-- FEATURED BUSINESSES -->
    <section class="marketplace" id="isletmeler">
        <div class="container">
            <div class="marketplace-head" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem; margin-bottom:2.5rem;">
                <div class="section-head" style="margin-bottom:0; text-align:left;">
                    <span class="hero__kicker" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd; margin-bottom:0.5rem; display:inline-block;">✦ Başarı Hikayeleri</span>
                    <h2>BooKi ile Dijitalleşen Öncü İşletmeler</h2>
                    <p>Kuaför, güzellik salonu, klinik ve restoranlar BooKi ile randevularını ve müşteri deneyimini sorunsuz yönetiyor.</p>
                </div>
            </div>

            <?php if (empty(vars('featured_tenants'))): ?>
                <div style="background:#ffffff; border:2px dashed #cbd5e1; border-radius:var(--radius); padding:3rem; text-align:center;">
                    <div style="font-size:2.5rem; margin-bottom:0.5rem;">✨</div>
                    <h3 style="color:var(--navy); font-size:1.25rem; margin-bottom:0.5rem;">Siz de Yerinizi Alın!</h3>
                    <p style="color:var(--muted); max-width:32rem; margin:0 auto 1.5rem;">İşletmenizi dakikalar içinde kaydedin, kendi online randevu sayfanızı hemen paylaşmaya başlayın.</p>
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
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.4rem;">
                                    <span style="display:inline-block; background:#e0f2fe; color:#0369a1; font-size:0.72rem; font-weight:700; padding:0.2rem 0.6rem; border-radius:999px;">
                                        <?= e($tenant['category'] ?? 'Hizmet & Bakım') ?>
                                    </span>
                                    <span style="color:#10b981; font-size:0.76rem; font-weight:700;">● Aktif</span>
                                </div>
                                <h3 style="font-size:1.15rem; margin-bottom:0.3rem;"><?= e($tenant['company_name'] ?? $tenant['subdomain']) ?></h3>
                                <div class="tenant-card__meta">
                                    <?= !empty($tenant['city']) ? '📍 ' . e($tenant['city']) : '📍 Türkiye' ?>
                                </div>
                                <div class="tenant-card__stars">
                                    <span style="color:#f59e0b;"><?= str_repeat('★', max(1, (int) round($avg_rating))) ?></span>
                                    <strong style="color:var(--navy); margin-left:4px;"><?= $avg_rating > 0 ? number_format($avg_rating, 1) : '5.0' ?></strong>
                                    <span style="color:var(--muted); font-size:0.8rem;"><?= $review_count > 0 ? ('(' . $review_count . ' değerlendirme)') : '(Onaylı İşletme)' ?></span>
                                </div>
                            </div>
                            <div style="margin-top:1.2rem; display:flex; gap:0.5rem;">
                                <a class="btn btn--primary btn--sm" style="flex:1; text-align:center;" href="<?= e($biz_booking_url) ?>" target="_blank" rel="noopener">Online Randevu Al →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- PRICING & SECTOR BAREM SELECTOR -->
    <section class="pricing" id="fiyatlandirma" style="padding: 5rem 0; background: #ffffff; border-top:1px solid #e2e8f0;">
        <div class="container">
            <div class="section-head" style="text-align: center; margin-bottom: 2.5rem;">
                <span class="hero__kicker" style="display:inline-block; margin-bottom: 0.5rem; background:#ecfdf5; color:#065f46; border-color:#a7f3d0;">
                    ✦ Şeffaf, Adil & Sektörel Paketler
                </span>
                <h2>İşletmenizin Büyüklüğüne Göre Ölçeklenen Planlar</h2>
                <p>İster tek kişilik stüdyo, ister kurumsal çok şubeli zincir; tüm özellikler açık, adil kapasite baremleri.</p>
                <div style="display:inline-flex; align-items:center; gap:0.5rem; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:0.9rem; font-weight:600; padding:0.65rem 1.4rem; border-radius:9999px; margin-top:1.25rem;">
                    <span>✨</span>
                    <span><strong>Tüm özellikler açık!</strong> POS, Adisyon, Raporlar, e-Fatura, Stok ve tüm modüller her pakette aktif. Tek istisna: Ücretsiz pakette AI Asistan hariçtir.</span>
                </div>
            </div>

            <!-- Billing Cycle Switch & Sector Selector -->
            <div style="display:flex; flex-direction:column; align-items:center; gap:1.25rem; margin-bottom: 3rem;">
                <!-- Billing cycle switch -->
                <div style="display:flex; align-items:center; background:#f1f5f9; padding:4px; border-radius:9999px; border:1px solid #e2e8f0;">
                    <button type="button" id="btn-billing-monthly" class="btn-billing-toggle active" onclick="setBillingCycle('monthly')" style="border:none; padding:0.5rem 1.4rem; border-radius:9999px; font-weight:700; font-size:0.88rem; cursor:pointer; background:#ffffff; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                        Aylık Ödeme
                    </button>
                    <button type="button" id="btn-billing-yearly" class="btn-billing-toggle" onclick="setBillingCycle('yearly')" style="border:none; padding:0.5rem 1.4rem; border-radius:9999px; font-weight:700; font-size:0.88rem; cursor:pointer; background:transparent; color:#64748b;">
                        Yıllık Peşin <span style="background:#10b981; color:#fff; font-size:0.72rem; padding:0.18rem 0.5rem; border-radius:9999px; margin-left:0.35rem; font-weight:800;">%20 İndirim</span>
                    </button>
                </div>

                <!-- Sector & subsector selector -->
                <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; justify-content:center; background:#f8fafc; padding:0.85rem 1.5rem; border-radius:14px; border:1px solid #cbd5e1; max-width:850px; width:100%;">
                    <span style="font-size:0.88rem; font-weight:800; color:#0f172a;">🏢 Sektörünüze Özel Baremler:</span>
                    <select id="pricing-sector-select" onchange="onPricingSectorChanged()" style="padding:0.45rem 0.85rem; font-size:0.88rem; border:1px solid #cbd5e1; border-radius:8px; background:#fff; font-weight:700; color:#1e293b;">
                        <?php 
                        $main_sectors = function_exists('get_main_sectors') ? get_main_sectors() : [];
                        foreach ($main_sectors as $sec_name => $sec_meta): ?>
                            <option value="<?= e($sec_name) ?>"><?= e(($sec_meta['icon'] ?? '') . ' ' . $sec_name) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="pricing-subsector-select" onchange="onPricingSubsectorChanged()" style="padding:0.45rem 0.85rem; font-size:0.88rem; border:1px solid #cbd5e1; border-radius:8px; background:#fff; font-weight:700; color:#1e293b; min-width:200px;">
                        <!-- populated via JS -->
                    </select>
                </div>
            </div>

            <!-- PRICING GRID (5 TIERS) -->
            <div class="pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); gap: 1.25rem; align-items:stretch;">
                <!-- 1. ÜCRETSİZ -->
                <div class="pricing-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 1.75rem 1.4rem; display: flex; flex-direction: column; position: relative;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #f1f5f9; border-radius: 20px; font-size: 0.72rem; font-weight: 700; color: #475569;">BAŞLANGIÇ</div>
                    <h3 style="margin-top: 0.85rem; font-size: 1.25rem; color:#0f172a;">Ücretsiz</h3>
                    <div style="margin: 0.5rem 0 0.2rem;">
                        <span class="price-val" style="font-size: 2rem; font-weight: 800; color: var(--navy);">0 ₺</span>
                        <span class="price-period" style="font-size: 0.85rem; font-weight: 500; color: var(--muted);">/ ömür boyu</span>
                    </div>
                    <div style="font-size:0.75rem; color:#64748b; font-weight:500; min-height:1.2rem;">Kredi kartı gerekmez</div>
                    <p style="font-size: 0.82rem; color: var(--muted); margin-bottom: 1rem;">Tek kişilik stüdyolar ve yeni başlayanlar için.</p>

                    <!-- Dynamic Baremler -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.75rem; margin-bottom:1rem;">
                        <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#64748b; margin-bottom:0.25rem;">Sektörel Kapasite</div>
                        <div id="barem-free" style="font-size:0.84rem; font-weight:700; color:#0f172a; line-height:1.35;">1-2 Koltuk / 1 Personel / 40 Randevu</div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem; font-size: 0.84rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.45rem;">
                        <li>✓ Tüm İşletme & Randevu Modülleri</li>
                        <li>✓ Müşteri Rehberi & Raporlar</li>
                        <li>✓ POS, Adisyon & Fatura Entegrasyonu</li>
                        <li>✓ Google Takvim Senkronizasyonu</li>
                        <li>✓ Online Rezervasyon Sayfası</li>
                        <li style="color: #ef4444; font-weight: 700;">✕ AI Asistan (Ücretsiz Hariçtir)</li>
                    </ul>
                    <a class="btn btn--outline btn--sm" style="width: 100%; text-align: center;" href="<?= e(vars('portal_url')) ?>">Hemen Başla</a>
                </div>

                <!-- 2. BAŞLANGIÇ -->
                <div class="pricing-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius); padding: 1.75rem 1.4rem; display: flex; flex-direction: column; position: relative;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #e0f2fe; border-radius: 20px; font-size: 0.72rem; font-weight: 700; color: #0369a1;">TEMEL İŞLETME</div>
                    <h3 style="margin-top: 0.85rem; font-size: 1.25rem; color:#0f172a;">Başlangıç</h3>
                    <div style="margin: 0.5rem 0 0.2rem;">
                        <span class="price-val" id="price-basic" style="font-size: 2rem; font-weight: 800; color: var(--navy);">1.250 ₺</span>
                        <span class="price-period" style="font-size: 0.85rem; font-weight: 500; color: var(--muted);">/ ay</span>
                    </div>
                    <div class="annual-note" id="annual-basic" style="font-size:0.75rem; color:#059669; font-weight:600; min-height:1.2rem;"></div>
                    <p style="font-size: 0.82rem; color: var(--muted); margin-bottom: 1rem;">Büyüyen ve randevularını otomatikleştiren işletmeler.</p>

                    <!-- Dynamic Baremler -->
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:0.6rem 0.75rem; margin-bottom:1rem;">
                        <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#15803d; margin-bottom:0.25rem;">Sektörel Kapasite</div>
                        <div id="barem-basic" style="font-size:0.84rem; font-weight:700; color:#0f172a; line-height:1.35;">3-5 Koltuk / 3-5 Personel / 250 Randevu</div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem; font-size: 0.84rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.45rem;">
                        <li style="color:#059669; font-weight:700;">✓ 🤖 Çok Kanallı AI Asistan DAHİL</li>
                        <li>✓ Tüm Özellikler & Modüller Açık</li>
                        <li>✓ WhatsApp Hatırlatma & Bildirimler</li>
                        <li>✓ Raporlar & Detaylı KPI Analizi</li>
                        <li>✓ Ürün Satışı & Stok Takibi</li>
                        <li>✓ PWA Mobil Uygulama Desteği</li>
                    </ul>
                    <a class="btn btn--primary btn--sm" style="width: 100%; text-align: center;" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Dene</a>
                </div>

                <!-- 3. ORTA -->
                <div class="pricing-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius); padding: 1.75rem 1.4rem; display: flex; flex-direction: column; position: relative;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #fef3c7; border-radius: 20px; font-size: 0.72rem; font-weight: 700; color: #b45309;">BÜYÜYEN İŞLETME</div>
                    <h3 style="margin-top: 0.85rem; font-size: 1.25rem; color:#0f172a;">Orta</h3>
                    <div style="margin: 0.5rem 0 0.2rem;">
                        <span class="price-val" id="price-pro" style="font-size: 2rem; font-weight: 800; color: var(--navy);">2.450 ₺</span>
                        <span class="price-period" style="font-size: 0.85rem; font-weight: 500; color: var(--muted);">/ ay</span>
                    </div>
                    <div class="annual-note" id="annual-pro" style="font-size:0.75rem; color:#059669; font-weight:600; min-height:1.2rem;"></div>
                    <p style="font-size: 0.82rem; color: var(--muted); margin-bottom: 1rem;">Orta ölçekli merkezler, klinikler ve salonlar.</p>

                    <!-- Dynamic Baremler -->
                    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:0.6rem 0.75rem; margin-bottom:1rem;">
                        <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#b45309; margin-bottom:0.25rem;">Sektörel Kapasite</div>
                        <div id="barem-pro" style="font-size:0.84rem; font-weight:700; color:#0f172a; line-height:1.35;">6-10 Koltuk / 6-10 Personel / 750 Randevu</div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem; font-size: 0.84rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.45rem;">
                        <li style="color:#059669; font-weight:700;">✓ 🤖 Çok Kanallı AI Asistan DAHİL</li>
                        <li>✓ Tüm Özellikler & Modüller Açık</li>
                        <li>✓ e-Fatura & Ön Muhasebe Entegrasyonu</li>
                        <li>✓ Gelişmiş Pazarlama & Kampanyalar</li>
                        <li>✓ Paket ve Seans Satış Takibi</li>
                        <li>✓ Hakediş & Personel Prim Yönetimi</li>
                    </ul>
                    <a class="btn btn--primary btn--sm" style="width: 100%; text-align: center;" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Dene</a>
                </div>

                <!-- 4. PREMIUM (EN POPÜLER) -->
                <div class="pricing-card" style="background: #ffffff; border: 2px solid var(--primary); border-radius: var(--radius); padding: 1.75rem 1.4rem; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(27, 94, 100, 0.14); position: relative;">
                    <div class="badge" style="position: absolute; top: -12px; right: 1.25rem; background: var(--primary); color: #fff; padding: 0.25rem 0.8rem; border-radius: 20px; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.5px;">EN POPÜLER</div>
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: var(--primary-soft); border-radius: 20px; font-size: 0.72rem; font-weight: 700; color: var(--primary);">TAM PROFESYONEL</div>
                    <h3 style="margin-top: 0.85rem; font-size: 1.25rem; color:#0f172a;">Premium</h3>
                    <div style="margin: 0.5rem 0 0.2rem;">
                        <span class="price-val" id="price-premium" style="font-size: 2rem; font-weight: 800; color: var(--navy);">4.750 ₺</span>
                        <span class="price-period" style="font-size: 0.85rem; font-weight: 500; color: var(--muted);">/ ay</span>
                    </div>
                    <div class="annual-note" id="annual-premium" style="font-size:0.75rem; color:#059669; font-weight:600; min-height:1.2rem;"></div>
                    <p style="font-size: 0.82rem; color: var(--muted); margin-bottom: 1rem;">Geniş ekipler, klinikler ve kurumsal merkezler.</p>

                    <!-- Dynamic Baremler -->
                    <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:0.6rem 0.75rem; margin-bottom:1rem;">
                        <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#047857; margin-bottom:0.25rem;">Sektörel Kapasite</div>
                        <div id="barem-premium" style="font-size:0.84rem; font-weight:700; color:#0f172a; line-height:1.35;">11-20 Koltuk / 11-20 Personel / 2.000 Randevu</div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem; font-size: 0.84rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.45rem;">
                        <li style="color:#059669; font-weight:700;">✓ 🤖 Çok Kanallı AI Asistan DAHİL</li>
                        <li>✓ Çoklu Şube (Branches) Desteği</li>
                        <li>✓ Özel Alan Adı (Custom Domain)</li>
                        <li>✓ Markasız Arayüz (White-Label)</li>
                        <li>✓ Öncelikli 7/24 Destek Hattı</li>
                        <li>✓ Tüm Özellikler Sınırsız Açık</li>
                    </ul>
                    <a class="btn btn--primary btn--sm" style="width: 100%; text-align: center; background: var(--primary);" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Dene</a>
                </div>

                <!-- 5. ÖZEL -->
                <div class="pricing-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius); padding: 1.75rem 1.4rem; display: flex; flex-direction: column; position: relative;">
                    <div class="badge" style="display:inline-block; align-self:flex-start; padding: 0.25rem 0.6rem; background: #f3e8ff; border-radius: 20px; font-size: 0.72rem; font-weight: 700; color: #7e22ce;">KURUMSAL & ÖZEL</div>
                    <h3 style="margin-top: 0.85rem; font-size: 1.25rem; color:#0f172a;">Özel</h3>
                    <div style="margin: 0.5rem 0 0.2rem;">
                        <span class="price-val" style="font-size: 2rem; font-weight: 800; color: var(--navy);">Özel Teklif</span>
                    </div>
                    <div class="annual-note" style="font-size:0.75rem; color:#64748b; font-weight:500; min-height:1.2rem;">İşletmenize özel fiyatlandırma</div>
                    <p style="font-size: 0.82rem; color: var(--muted); margin-bottom: 1rem;">Büyük zincirler, hastaneler ve özel entegrasyonlar.</p>

                    <!-- Dynamic Baremler -->
                    <div style="background:#faf5ff; border:1px solid #f3e8ff; border-radius:8px; padding:0.6rem 0.75rem; margin-bottom:1rem;">
                        <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; color:#7e22ce; margin-bottom:0.25rem;">Sektörel Kapasite</div>
                        <div id="barem-custom" style="font-size:0.84rem; font-weight:700; color:#0f172a; line-height:1.35;">Sınırsız Kaynak & Personel</div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 0 0 1.25rem; font-size: 0.84rem; color: var(--text); flex-grow: 1; display: flex; flex-direction: column; gap: 0.45rem;">
                        <li style="color:#059669; font-weight:700;">✓ 🤖 Sınırsız AI Asistan Kullanımı</li>
                        <li>✓ Özel Veri Aktarımı & Kurulum</li>
                        <li>✓ Özel API & ERP Entegrasyonları</li>
                        <li>✓ Kurumsal SLA & Dedike Hesap Yöneticisi</li>
                        <li>✓ Çoklu Lokasyon & Franchise Yönetimi</li>
                    </ul>
                    <a class="btn btn--outline btn--sm" style="width: 100%; text-align: center;" href="#iletisim">İletişime Geçin</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ (SIKÇA SORULAN SORULAR) -->
    <section class="faq-section" id="sss" style="padding:5rem 0;">
        <div class="container">
            <div class="section-head">
                <span class="hero__kicker" style="background:#f1f5f9; color:#475569; border-color:#cbd5e1; margin-bottom:0.6rem;">✦ Merak Edilenler</span>
                <h2>Sıkça Sorulan Sorular</h2>
                <p>BooKi paketleri, özellikler ve kapasite baremleri hakkında merak ettiğiniz tüm detaylar.</p>
            </div>

            <div class="faq-list">
                <div class="faq-item active">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>BooKi'de "Tüm özellikler açık" politikası ne anlama geliyor?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Diğer platformların aksine; POS, adisyon, müşteri CRM, detaylı raporlar, stok takibi ve online rezervasyon gibi temel operasyonel modüllerimizi paketler arkasına gizlemiyoruz. En küçük paketten en büyüğüne kadar tüm işletme özellikleri açıktır. Paketler yalnızca işletmenizin kapasitesine (koltuk/istasyon, personel ve aylık randevu baremi) göre ölçeklenir.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>Ücretsiz paketin farkı nedir, hangi özellik hariçtir?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Ücretsiz paketimiz tek kişilik stüdyolar ve işe yeni başlayan girişimciler için ömür boyu ücretsizdir. Tek istisna: <strong>Yapay Zeka (AI) Asistanı Ücretsiz pakette yer almaz</strong>. Başlangıç, Orta, Premium ve Özel paketlerimizde ise Çok Kanallı AI Asistanı sınırsız ve tam entegre olarak sunulur.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>Randevu başına veya cirodan komisyon kesiyor musunuz?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Kesinlikle hayır! BooKi %0 komisyon güvencesiyle çalışır. İster ayda 50 randevu alın, ister 5.000; aldığınız randevular üzerinden hiçbir komisyon veya gizli masraf ödemezsiniz. Müşterileriniz doğrudan sizin markanızla randevu oluşturur.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>Sektörel baremler (Koltuk / Personel / Randevu) nasıl çalışır?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        9 ana sektörümüz ve 168 alt işletme tipimizin operasyon yapısı farklıdır (örneğin kuaför için koltuk, klinik için hekim/oda, restoran için masa sayısı esastır). İşletme tipinizi seçtiğinizde, kapasitenize en uygun kaynak ve randevu baremi otomatik olarak tanımlanır. Bareminizi aştığınızda sistem sizi uyarır ve kolayca bir üst pakete geçebilirsiniz.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>WhatsApp ve AI Asistanı nasıl bağlanır?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        İşletmenizin resmi WhatsApp Business numarasını Meta Cloud API aracılığıyla panelimizden tek tıkla bağlayabilirsiniz. AI asistanınız anında müşterilerinizden gelen mesajları yanıtlamaya, uygun boş saatleri sunmaya ve randevuları takviminize işlemeye başlar.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>Kendi alan adımı (Custom Domain) ve markamı kullanabilir miyim?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Evet! Premium ve Özel paketlerimizde <code>randevu.sizinmarkaniz.com</code> gibi kendi alan adınızı ve logonuzu (White-Label) kullanarak müşterilerinize tamamen markanıza ait profesyonel bir randevu deneyimi yaşatabilirsiniz.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)">
                        <span>Müşteri verilerimiz KVKK ve GDPR açısından güvende mi?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        Evet. Tüm verileriniz 256-bit SSL şifreleme ve günlük otomatik yedeklemelerle güvenli sunucularda saklanır. Müşteri PII (Kişisel Bilgiler) veritabanında şifreli olarak tutulur ve KVKK/GDPR uyumlu veri aktarım ve silme mekanizmaları ile korunur.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta" style="background:radial-gradient(900px 400px at 20% 0%, rgba(255, 255, 255, .12), transparent 55%), linear-gradient(160deg, #1b5e64 0%, #0a1724 120%); color:#fff; text-align:center; padding:5rem 0;">
        <div class="container" style="max-width:700px;">
            <h2 style="font-family:'DM Serif Display', Georgia, serif; font-size:clamp(2rem, 3.5vw, 2.8rem); margin-bottom:1rem; color:#fff;">İşletmenizi dijitalleştirmeye hazır mısınız?</h2>
            <p style="color:rgba(255,255,255,0.85); font-size:1.1rem; line-height:1.6; margin-bottom:2rem;">BooKi'yi bugün 14 gün boyunca ücretsiz deneyin. Kredi kartı gerekmez, 15 dakika içinde online randevu almaya başlayın.</p>
            <a class="btn btn--light" style="font-size:1.05rem; padding:0.85rem 2rem;" href="<?= e(vars('portal_url')) ?>">14 Gün Ücretsiz Başlayın</a>
        </div>
    </section>

    <!-- İLETİŞİM / CONTACT -->
    <section class="contact-section" id="iletisim" style="padding: 5rem 0; background: #f8fafc; border-top: 1px solid #e2e8f0;">
        <div class="container">
            <div class="section-head" style="text-align: center; margin-bottom: 3.5rem;">
                <span class="hero__kicker" style="display:inline-block; margin-bottom: 0.5rem; background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">✦ Doğrudan Ulaşın</span>
                <h2>İletişim Bilgileri</h2>
                <p>Sorularınız, kurumsal talepleriniz ve teknik destek için ekibimizle dilediğiniz zaman iletişime geçebilirsiniz.</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.75rem; margin-bottom: 2rem;">
                <!-- Firma / Ticari Bilgiler -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1.25rem;">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 style="font-size: 1.2rem; color: var(--navy); margin-bottom: 0.75rem;">Firma & Unvan Bilgileri</h3>
                    <p style="font-size: 0.95rem; color: var(--text); line-height: 1.6; margin-bottom: 0.5rem;">
                        <strong>Ticari Unvan:</strong> Ki Software / Ki Business Solutions (Miraç Murat Kılınç)
                    </p>
                    <p style="font-size: 0.9rem; color: var(--muted); line-height: 1.5;">
                        Online randevu, takvim, müşteri ve ödeme yönetim platformu sağlayıcısı.
                    </p>
                </div>

                <!-- Adres Bilgileri -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1.25rem;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 style="font-size: 1.2rem; color: var(--navy); margin-bottom: 0.75rem;">Adres & Konum</h3>
                    <p style="font-size: 0.95rem; color: var(--text); line-height: 1.6; margin-bottom: 0.5rem;">
                        <strong>Merkez Adres:</strong> Çekirge Mh. Süleyman Sk. No 29 Osmangazi Bursa
                    </p>
                    <p style="font-size: 0.88rem; color: var(--muted);">
                        Çalışma Saatleri: Pazartesi - Cuma 09:00 - 18:00
                    </p>
                </div>

                <!-- İletişim Kanalları -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius); padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1.25rem;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 style="font-size: 1.2rem; color: var(--navy); margin-bottom: 0.75rem;">İletişim & Destek</h3>
                    <p style="font-size: 0.95rem; color: var(--text); line-height: 1.6; margin-bottom: 0.4rem;">
                        <strong>Telefon:</strong> <a href="tel:+908508850024" style="color: var(--primary); font-weight: 600;">+90 (850) 885 00 24</a> / <a href="tel:+905062505562" style="color: var(--primary); font-weight: 600;">0506 250 55 62</a>
                    </p>
                    <p style="font-size: 0.95rem; color: var(--text); line-height: 1.6; margin-bottom: 0.4rem;">
                        <strong>E-posta (Destek):</strong> <a href="mailto:support@kibusiness.co" style="color: var(--primary); font-weight: 600;">support@kibusiness.co</a>
                    </p>
                    <p style="font-size: 0.95rem; color: var(--text); line-height: 1.6;">
                        <strong>E-posta (Finans & POS):</strong> <a href="mailto:finance@kibusiness.co" style="color: var(--primary); font-weight: 600;">finance@kibusiness.co</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi">
                    <span class="brand__logo">BOO·KI</span>
                </a>
                <p style="margin-top:1rem; max-width:22rem; font-size:.9rem; color:rgba(255,255,255,0.75); line-height:1.6;">
                    9 ana sektör ve 168 işletme tipi için online randevu, salon takvimi, çok kanallı AI asistanı ve adisyon yönetim sistemi.
                </p>
                <p style="margin-top:0.75rem; max-width:22rem; font-size:.85rem; color:rgba(255,255,255,.6); line-height:1.5;">
                    <i class="fas fa-map-marker-alt" style="margin-right:4px;"></i> Çekirge Mh. Süleyman Sk. No 29 Osmangazi Bursa
                </p>
                <div style="margin-top:1rem; display:flex; gap:0.75rem;">
                    <a href="https://instagram.com" target="_blank" rel="noopener" style="font-size:1.2rem; color:rgba(255,255,255,0.7);"><i class="fab fa-instagram"></i></a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener" style="font-size:1.2rem; color:rgba(255,255,255,0.7);"><i class="fab fa-linkedin"></i></a>
                    <a href="https://twitter.com" target="_blank" rel="noopener" style="font-size:1.2rem; color:rgba(255,255,255,0.7);"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div>
                <div style="font-weight:700; color:#fff; margin-bottom:1rem; font-size:0.95rem;">Sektörler</div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem; font-size:0.88rem;">
                    <li><a href="#sektorler">Güzellik & Bakım</a></li>
                    <li><a href="#sektorler">Restoran & Yeme-İçme</a></li>
                    <li><a href="#sektorler">Spor & Fitness</a></li>
                    <li><a href="#sektorler">Sağlık & Klinik</a></li>
                    <li><a href="#sektorler">Otomotiv & Servis</a></li>
                    <li><a href="#sektorler">Deneyim & Eğlence</a></li>
                    <li><a href="#sektorler">Konaklama & Turizm</a></li>
                    <li><a href="#sektorler">Eğitim & Kurs</a></li>
                </ul>
            </div>

            <div>
                <div style="font-weight:700; color:#fff; margin-bottom:1rem; font-size:0.95rem;">Ürün & Çözüm</div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem; font-size:0.88rem;">
                    <li><a href="#yapay-zeka">Çok Kanallı AI Asistan</a></li>
                    <li><a href="#ozellikler">Online Randevu & Takvim</a></li>
                    <li><a href="#ozellikler">Adisyon & Entegre POS</a></li>
                    <li><a href="#ozellikler">WhatsApp Hatırlatmaları</a></li>
                    <li><a href="#fiyatlandirma">Paketler & Fiyatlar</a></li>
                    <li><a href="#sss">Sıkça Sorulan Sorular</a></li>
                    <li><a href="<?= e(vars('portal_url')) ?>" onclick="openTenantLoginModal(event)">İşletme Girişi</a></li>
                </ul>
            </div>

            <div>
                <div style="font-weight:700; color:#fff; margin-bottom:1rem; font-size:0.95rem;">Yasal & Güvenlik</div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.5rem; font-size:0.88rem;">
                    <li><a href="<?= base_url('privacy') ?>">Gizlilik Politikası (KVKK / GDPR)</a></li>
                    <li><a href="<?= base_url('terms') ?>">Kullanım Şartları</a></li>
                    <li><a href="<?= base_url('about') ?>">Hakkımızda</a></li>
                    <li><a href="<?= base_url('mesafeli-satis') ?>">Mesafeli Satış Sözleşmesi</a></li>
                    <li><a href="<?= base_url('teslimat-iade') ?>">Teslimat ve İade Politikası</a></li>
                    <li><a href="#iletisim">İletişim</a></li>
                </ul>
            </div>
        </div>
        <!-- PAYMENT COMPLIANCE LOGOS -->
        <div style="margin-bottom: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; gap: 1.25rem; flex-wrap: wrap;">
            <span style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.5); font-weight: 600;">Güvenli Ödeme Altyapısı:</span>
            <img src="<?= asset_url('assets/img/iyzico/footer_iyzico_ile_ode_color.svg') ?>" alt="iyzico ile Öde" style="height:26px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/visa.svg') ?>" alt="Visa" style="height:20px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/mastercard.svg') ?>" alt="Mastercard" style="height:22px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/troy.svg') ?>" alt="Troy" style="height:20px;width:auto;border-radius:3px;" loading="lazy">
        </div>

        <div class="footer-bottom">
            <div>
                © <?= date('Y') ?> BooKi by Ki Software. Tüm hakları saklıdır. %0 Komisyon Garantisi.
            </div>
            <div>
                <span style="color:#10b981; margin-right:8px;">● 256-Bit SSL Şifreleme</span>
                <span>KVKK & GDPR Uyumlu Altyapı</span>
            </div>
        </div>
    </div>
</footer>

<!-- TENANT LOGIN MODAL -->
<div class="tenant-modal-backdrop" id="tenantLoginModal" onclick="closeTenantLoginModalOnBackdrop(event)">
    <div class="tenant-login-dialog" role="dialog" aria-modal="true" aria-labelledby="tenantModalTitle">
        <button type="button" class="tenant-login-close" onclick="closeTenantLoginModal()" aria-label="Kapat">×</button>
        <div class="tenant-modal-badge">
            <i class="fas fa-lock"></i> İşletme Girişi
        </div>
        <h3 id="tenantModalTitle" style="font-size:1.35rem; font-weight:800; color:var(--navy); margin-bottom:0.4rem;">Panelinize Giriş Yapın</h3>
        <p style="font-size:0.88rem; color:var(--muted); margin-bottom:1.4rem; line-height:1.45;">
            Kayıt olurken belirlediğiniz işletme adınızı (subdomain) girerek yönetim panelinize doğrudan ulaşabilirsiniz.
        </p>

        <form id="tenantLoginForm" onsubmit="handleTenantLoginSubmit(event)">
            <div class="tenant-input-group">
                <label for="tenantSubdomainInput" style="display:block; font-size:0.83rem; font-weight:700; color:var(--navy); margin-bottom:0.4rem;">İşletme Adınız (Subdomain)</label>
                <div class="tenant-input-wrap">
                    <i class="fas fa-store"></i>
                    <input
                        type="text"
                        id="tenantSubdomainInput"
                        name="subdomain"
                        placeholder="ornek: salonflora"
                        autocomplete="off"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        oninput="updateTenantLoginPreview(this.value)"
                    >
                </div>
                <div class="tenant-modal-preview" id="tenantLoginPreview">
                    Gidilecek adres: <strong>https://<span id="previewSubdomain">isletmeadi</span>-<?= e(vars('app_domain') ?: 'bookiapp.kibusiness.co') ?>/backend</strong>
                </div>
            </div>

            <button type="submit" class="tenant-modal-btn">
                <span>Panele Git</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div style="margin-top:1.2rem; text-align:center; font-size:0.82rem; color:var(--muted);">
            İşletme adınızı hatırlamıyor musunuz?
            <a href="mailto:support@kibusiness.co" style="color:var(--primary); font-weight:600;">Destek Alın</a>
        </div>
    </div>
</div>

<!-- SCRIPTS & INTERACTION -->
<script>
    const pricingMatrixData = <?= json_encode(function_exists('get_sector_pricing_matrix') ? get_sector_pricing_matrix() : [], JSON_UNESCAPED_UNICODE) ?>;
    const mainSectorsData = {
        "Güzellik / Kişisel Bakım": {
            icon: "💅",
            title: "Güzellik Merkezleri, Kuaförler & Bakım Stüdyoları",
            desc: "Koltuk, kabin ve uzman bazlı randevu akışı, seans ve paket satışları, WhatsApp teyit mesajları ve adisyon tek ekranda.",
            features: [
                "Koltuk / Kabin & İstasyon Takibi",
                "Uzman Bazlı Çalışma & Prim",
                "Seans & Paket Satış Düşümü",
                "WhatsApp Hatırlatma & Onay"
            ]
        },
        "Restoran / Yeme-İçme": {
            icon: "🍽️",
            title: "Restoranlar, Kafeler & Gastronomi Mekanları",
            desc: "Masa planı, kişi sayısı kontrolü, adisyon takibi ve yemek servisi saatlerine göre optimize edilmiş rezervasyon sistemi.",
            features: [
                "Masa & Salon Yerleşim Planı",
                "Kişi Sayısı & Saat Kotası",
                "Adisyon & Entegre POS",
                "Depozito & İptal Önleme"
            ]
        },
        "Spor / Fitness": {
            icon: "🏋️",
            title: "Spor Salonları, Stüdyolar & Kişisel Antrenörler",
            desc: "Grup dersi kontenjanı, saha/kort kiralama, antrenör takvimi ve üyelik paketleri tek bir altyapıda.",
            features: [
                "Saha / Kort & Ekipman Rezervasyonu",
                "Grup Dersi Kontenjan Yönetimi",
                "Üyelik & Kalan Ders Takibi",
                "Online Ödeme & POS"
            ]
        },
        "Sağlık / Uzmanlık": {
            icon: "🩺",
            title: "Klinikler, Doktorlar, Diyetisyenler & Danışmanlar",
            desc: "KVKK uyumlu şifreli danışan dosyaları, seans takvimi, bekleme odası yönetimi ve e-fatura entegrasyonu.",
            features: [
                "KVKK Uyumlu Şifreli Anamnez",
                "Doktor / Uzman Takvim Ayrımı",
                "Akıllı Bekleme Listesi",
                "GİB e-Fatura / e-Arşiv"
            ]
        },
        "Otomotiv": {
            icon: "🚗",
            title: "Oto Servisleri, Ekspertiz & Detailing Merkezleri",
            desc: "Lift ve servis istasyonu bazlı randevu, araç plaka takibi, parça/hizmet faturası ve SMS bilgilendirmeleri.",
            features: [
                "Lift & İstasyon Kapasite Planı",
                "Plaka & Araç Geçmişi",
                "Yedek Parça & İşçilik Adisyonu",
                "Servis Durum SMS Bildirimi"
            ]
        },
        "Deneyim / Eğlence": {
            icon: "🎯",
            title: "Kaçış Oyunları, Atölyeler, VR & Aktivite Merkezleri",
            desc: "Oyun odası veya seans bazlı rezervasyon, online bilet satışı, grup kayıtları ve otomatik geri bildirim.",
            features: [
                "Oda / Parkur Seans Planlaması",
                "Kişi Başı veya Grup Fiyatlandırma",
                "Online Depozito & Iyzico",
                "Otomatik Puan & Yorum Talebi"
            ]
        },
        "Konaklama": {
            icon: "🏨",
            title: "Butik Oteller, Bungalovlar & Glamping Tesisleri",
            desc: "Oda/bungalov müsaitliği, giriş-çıkış tarihleri, ek hizmetler ve sıfır komisyonlu doğrudan rezervasyon.",
            features: [
                "Oda & Konaklama Ünitesi Takibi",
                "Giriş / Çıkış (Check-in/out) Yönetimi",
                "Ekstra Hizmet & Adisyon",
                "%0 Komisyon ile Doğrudan Satış"
            ]
        },
        "Eğitim / Kurs": {
            icon: "📚",
            title: "Müzik, Dil, Sanat Kursları & Özel Ders Eğitmenleri",
            desc: "Sınıf kapasitesi, eğitmen ders programı, veli bilgilendirme ve otomatik ders tekrarları.",
            features: [
                "Sınıf & Eğitmen Programı",
                "Birebir & Grup Dersi Yönetimi",
                "Otomatik Ders Hatırlatması",
                "Paket Ders / Kredi Düşümü"
            ]
        },
        "Profesyonel Hizmet": {
            icon: "💼",
            title: "Avukatlar, Mali Müşavirler, Fotoğrafçılar & Ajanslar",
            desc: "Toplantı odası rezervasyonu, danışmanlık saatleri, dosya notları ve peşin randevu tahsilatı.",
            features: [
                "Saatlik Danışmanlık Rezervasyonu",
                "Toplantı Odası & Ekipman Tahsisi",
                "Online Ön Ödeme & Fatura",
                "Google & Outlook Takvim Senkronu"
            ]
        }
    };

    let activeSectorName = "Güzellik / Kişisel Bakım";
    let currentBillingCycle = 'monthly';

    function switchSectorTab(sectorName, btnElem) {
        activeSectorName = sectorName;
        document.querySelectorAll('.sector-tab-btn').forEach(b => b.classList.remove('active'));
        if (btnElem) btnElem.classList.add('active');

        const meta = mainSectorsData[sectorName] || {
            icon: "🏢",
            title: sectorName,
            desc: "İşletmenizin kapasite ve operasyonlarına tam uyumlu randevu altyapısı.",
            features: ["7/24 Online Randevu", "Takvim & Personel Yönetimi", "WhatsApp Bildirimleri", "Adisyon & POS"]
        };

        document.getElementById('active-sector-icon').textContent = meta.icon;
        document.getElementById('active-sector-group').textContent = sectorName;
        document.getElementById('active-sector-title').textContent = meta.title;
        document.getElementById('active-sector-desc').textContent = meta.desc;

        const featContainer = document.getElementById('active-sector-features');
        featContainer.innerHTML = '';
        meta.features.forEach(f => {
            const div = document.createElement('div');
            div.style.cssText = "font-size:0.85rem; font-weight:600; color:var(--navy); display:flex; align-items:center; gap:0.4rem;";
            div.innerHTML = `<i class="fas fa-check-circle" style="color:var(--primary);"></i> ${f}`;
            featContainer.appendChild(div);
        });

        // Populate subsector pills
        const pillsContainer = document.getElementById('active-subsectors-pills');
        pillsContainer.innerHTML = '';
        const matchingSubsectors = [];

        for (const subName in pricingMatrixData) {
            if (pricingMatrixData[subName].main_sector === sectorName) {
                matchingSubsectors.push(subName);
            }
        }

        document.getElementById('subsector-count-badge').textContent = `${matchingSubsectors.length} İşletme Tipi`;

        matchingSubsectors.forEach(sub => {
            const pill = document.createElement('span');
            pill.className = 'subsector-pill';
            pill.textContent = sub;
            pill.title = "Fiyat ve baremini gör";
            pill.onclick = () => {
                selectSubsectorForPricing(sectorName, sub);
            };
            pillsContainer.appendChild(pill);
        });
    }

    function selectSectorForPricing() {
        const secSelect = document.getElementById('pricing-sector-select');
        secSelect.value = activeSectorName;
        onPricingSectorChanged();
    }

    function selectSubsectorForPricing(secName, subName) {
        const secSelect = document.getElementById('pricing-sector-select');
        secSelect.value = secName;
        onPricingSectorChanged();

        const subSelect = document.getElementById('pricing-subsector-select');
        subSelect.value = subName;
        onPricingSubsectorChanged();

        const pSection = document.getElementById('fiyatlandirma');
        if (pSection) {
            pSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function setBillingCycle(cycle) {
        currentBillingCycle = cycle;
        const btnM = document.getElementById('btn-billing-monthly');
        const btnY = document.getElementById('btn-billing-yearly');

        if (cycle === 'yearly') {
            btnY.style.background = '#ffffff';
            btnY.style.color = '#0f172a';
            btnY.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            btnM.style.background = 'transparent';
            btnM.style.color = '#64748b';
            btnM.style.boxShadow = 'none';

            document.getElementById('price-basic').textContent = '1.000 ₺';
            document.getElementById('price-pro').textContent = '1.950 ₺';
            document.getElementById('price-premium').textContent = '3.800 ₺';

            document.getElementById('annual-basic').textContent = 'Yıllık peşin 12.000 ₺ / yıl';
            document.getElementById('annual-pro').textContent = 'Yıllık peşin 23.400 ₺ / yıl';
            document.getElementById('annual-premium').textContent = 'Yıllık peşin 45.600 ₺ / yıl';
        } else {
            btnM.style.background = '#ffffff';
            btnM.style.color = '#0f172a';
            btnM.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            btnY.style.background = 'transparent';
            btnY.style.color = '#64748b';
            btnY.style.boxShadow = 'none';

            document.getElementById('price-basic').textContent = '1.250 ₺';
            document.getElementById('price-pro').textContent = '2.450 ₺';
            document.getElementById('price-premium').textContent = '4.750 ₺';

            document.getElementById('annual-basic').textContent = '';
            document.getElementById('annual-pro').textContent = '';
            document.getElementById('annual-premium').textContent = '';
        }
    }

    function onPricingSectorChanged() {
        const secSelect = document.getElementById('pricing-sector-select');
        const subSelect = document.getElementById('pricing-subsector-select');
        const chosenSec = secSelect.value;

        subSelect.innerHTML = '';
        const matching = [];
        for (const name in pricingMatrixData) {
            if (pricingMatrixData[name].main_sector === chosenSec) {
                matching.push(name);
            }
        }

        matching.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            subSelect.appendChild(opt);
        });

        if (matching.length > 0) {
            subSelect.value = matching[0];
        }
        onPricingSubsectorChanged();
    }

    function onPricingSubsectorChanged() {
        const subSelect = document.getElementById('pricing-subsector-select');
        const chosenSub = subSelect.value;
        const item = pricingMatrixData[chosenSub];

        if (!item || !item.tiers) return;

        const t = item.tiers;
        if (t.Free) document.getElementById('barem-free').textContent = t.Free.raw;
        if (t.Basic) document.getElementById('barem-basic').textContent = t.Basic.raw;
        if (t.Pro) document.getElementById('barem-pro').textContent = t.Pro.raw;
        if (t.Premium) document.getElementById('barem-premium').textContent = t.Premium.raw;
        if (t.Custom) document.getElementById('barem-custom').textContent = t.Custom.raw;
    }

    function toggleFaq(button) {
        const item = button.closest('.faq-item');
        const wasActive = item.classList.contains('active');
        document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('active'));
        if (!wasActive) {
            item.classList.add('active');
        }
    }

    // Modal logic
    function openTenantLoginModal(event) {
        if (event) event.preventDefault();
        const modal = document.getElementById('tenantLoginModal');
        if (modal) {
            modal.classList.add('open');
            const inp = document.getElementById('tenantSubdomainInput');
            if (inp) setTimeout(() => inp.focus(), 100);
        }
    }

    function closeTenantLoginModal() {
        const modal = document.getElementById('tenantLoginModal');
        if (modal) modal.classList.remove('open');
    }

    function closeTenantLoginModalOnBackdrop(event) {
        if (event.target && event.target.id === 'tenantLoginModal') {
            closeTenantLoginModal();
        }
    }

    function updateTenantLoginPreview(val) {
        const slug = (val || '').toLowerCase().replace(/[^a-z0-9-]/g, '').trim();
        const display = slug || 'isletmeadi';
        const span = document.getElementById('previewSubdomain');
        if (span) span.textContent = display;
    }

    function handleTenantLoginSubmit(event) {
        event.preventDefault();
        const input = document.getElementById('tenantSubdomainInput');
        const raw = (input ? input.value : '').toLowerCase().replace(/[^a-z0-9-]/g, '').trim();
        if (!raw) return;
        const appDomain = '<?= e(vars("app_domain") ?: "bookiapp.kibusiness.co") ?>';
        window.location.href = `https://${raw}-${appDomain}/backend`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        switchSectorTab("Güzellik / Kişisel Bakım", document.querySelector('.sector-tab-btn'));
        onPricingSectorChanged();
    });
</script>

</body>
</html>