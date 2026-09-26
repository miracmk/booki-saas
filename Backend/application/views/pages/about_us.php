<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="About BooKi by Ki Software — online appointment scheduling and booking platform for salons, spas, clinics and studios. Randevu ve salon yönetim sistemi hakkında bilgi.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('about') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BooKi — About Us / Hakkımızda">
    <meta property="og:description" content="About Us page for BooKi by Ki Software.">
    <meta property="og:url" content="<?= base_url('about') ?>">
    <link rel="icon" type="image/png" href="<?= asset_url('img/logo-16x16.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap" rel="stylesheet">
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
            --border: #e2e8f0;
            --radius: 14px;
            --shadow: 0 4px 20px rgba(10, 23, 36, .06);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--primary); text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
        .container { max-width: 960px; margin: 0 auto; padding: 0 1.5rem; }

        /* ---------- Header ---------- */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
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

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .lang-switch {
            display: inline-flex;
            background: #eef2f5;
            border-radius: 999px;
            padding: 3px;
            gap: 3px;
        }
        .lang-btn {
            border: none;
            background: transparent;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--muted);
            padding: 5px 14px;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .lang-btn.active {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(27, 94, 100, 0.25);
        }

        /* ---------- Hero ---------- */
        .policy-hero {
            background: linear-gradient(180deg, #ffffff 0%, var(--bg) 100%);
            padding: 3.5rem 0 2.5rem;
            border-bottom: 1px solid var(--border);
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: var(--primary-soft);
            color: var(--primary);
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .policy-hero h1 {
            font-size: 2.3rem;
            font-weight: 800;
            color: var(--navy);
            line-height: 1.25;
            margin-bottom: 0.75rem;
        }
        .policy-hero p {
            color: var(--muted);
            font-size: 1.05rem;
            max-width: 760px;
        }
        .meta-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1.25rem;
            font-size: 0.88rem;
            color: var(--muted);
        }
        .meta-strip strong { color: var(--text); }

        /* ---------- Content ---------- */
        .policy-content {
            padding: 3rem 0 4.5rem;
        }
        .policy-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 2.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 640px) {
            .policy-card { padding: 1.5rem; }
            .policy-hero h1 { font-size: 1.8rem; }
        }

        .policy-card h2 {
            font-size: 1.45rem;
            color: var(--navy);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-soft);
        }
        .policy-card h2:first-of-type { margin-top: 0; }
        .policy-card h3 {
            font-size: 1.15rem;
            color: var(--navy);
            margin: 1.4rem 0 0.6rem;
        }
        .policy-card p {
            margin-bottom: 1rem;
            color: var(--text);
        }
        .policy-card ul, .policy-card ol {
            margin: 0.75rem 0 1.25rem 1.4rem;
            color: var(--text);
        }
        .policy-card li { margin-bottom: 0.5rem; }

        .highlight-box {
            background: #f8fafc;
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin: 1.5rem 0;
        }
        .highlight-box h4 {
            color: var(--navy);
            font-size: 1.05rem;
            margin-bottom: 0.4rem;
        }
        .highlight-box p {
            margin-bottom: 0.5rem;
            font-size: 0.94rem;
        }
        .highlight-box p:last-child { margin-bottom: 0; }

        /* ---------- Footer ---------- */
        .site-footer {
            background: var(--navy);
            color: #94a3b8;
            padding: 3.5rem 0 2rem;
            font-size: 0.92rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2.5rem;
            margin-bottom: 2.5rem;
        }
        @media (max-width: 800px) {
            .footer-grid { grid-template-columns: 1fr; gap: 1.75rem; }
        }
        .site-footer h4 {
            color: #ffffff;
            font-size: 1rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .site-footer ul { list-style: none; margin: 0; padding: 0; }
        .site-footer li { margin-bottom: 0.55rem; }
        .site-footer a { color: #cbd5e1; font-weight: 500; }
        .site-footer a:hover { color: #ffffff; }
        .site-footer__bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }
        .payment-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 0.5rem;
            margin-bottom: 1.75rem;
        }
        .payment-row span {
            font-size: 0.78rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container">
        <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi">
            <span class="brand__logo">BOO·KI</span>
            <span class="brand__name">BooKi</span>
        </a>
        <div class="header-actions">
            <div class="lang-switch" role="tablist" aria-label="Language Switcher">
                <button type="button" class="lang-btn active" id="btnEn" onclick="switchLanguage('en')">English</button>
                <button type="button" class="lang-btn" id="btnTr" onclick="switchLanguage('tr')">Türkçe</button>
            </div>
            <a href="<?= base_url('/') ?>" style="font-size:0.9rem;">← Home</a>
        </div>
    </div>
</header>

<section class="policy-hero">
    <div class="container">
        <span class="hero-badge">🏢 About BooKi</span>
        <h1 id="heroTitle">About BooKi</h1>
        <p id="heroSubtitle">The online appointment scheduling and business management platform for service-oriented businesses.</p>
        <div class="meta-strip">
            <div><strong>Application:</strong> BooKi Appointment &amp; Booking Platform</div>
            <div><strong>Developer / Entity:</strong> Ki Software / Ki Business Solutions</div>
            <div><strong>Dedicated URL:</strong> <a href="https://booki.kibusiness.co">booki.kibusiness.co</a></div>
        </div>
    </div>
</section>

<main class="policy-content">
    <div class="container">

        <!-- ================= ENGLISH SECTION ================= -->
        <article class="policy-card" id="aboutEnglish">
            <h2>Who We Are</h2>
            <p>
                <strong>BooKi</strong> is an online appointment scheduling and business management platform developed by <strong>Ki Software</strong> (a division of <strong>Ki Business Solutions</strong>). We build practical, cloud-based tools for service-oriented businesses — including beauty salons, barbershops, spas, clinics and studios — so they can manage appointments, staff, customers and payments from a single dashboard.
            </p>

            <h2>What BooKi Offers</h2>
            <ul>
                <li>Online appointment booking and calendar management with double-booking prevention.</li>
                <li>Staff and commission management with session check-in / check-out tracking.</li>
                <li>Customer relationship management and reservation history.</li>
                <li>Secure online payments processed through iyzico (Visa and Mastercard supported).</li>
                <li>Third-party calendar synchronization, including Google Calendar.</li>
                <li>A public marketplace ("RandevuBurada") where businesses can be discovered by new customers.</li>
            </ul>

            <h2>Our Commitment</h2>
            <p>
                We are committed to reliability, security and privacy. BooKi handles personal data in accordance with the Turkish Personal Data Protection Law (KVKK) and the EU General Data Protection Regulation (GDPR). Payment transactions are processed exclusively through PCI-DSS compliant payment provider iyzico; card details never touch our servers.
            </p>

            <h2>Contact</h2>
            <p>
                <strong>Ki Software / Ki Business Solutions</strong><br>
                Address: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa, Türkiye<br>
                Website: <a href="https://kibusiness.co" target="_blank" rel="noopener">https://kibusiness.co</a><br>
                Email: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a><br>
                Legal Affairs: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>
            </p>
        </article>

        <!-- ================= TURKISH SECTION ================= -->
        <article class="policy-card" id="aboutTurkish" style="display:none;">
            <h2>Biz Kimiz</h2>
            <p>
                <strong>BooKi</strong>, <strong>Ki Software</strong> (<strong>Ki Business Solutions</strong> bünyesi) tarafından geliştirilen online randevu ve işletme yönetim platformudur. Kuaför, güzellik salonu, berber, spa, klinik ve stüdyo gibi hizmet işletmeleri için randevu, personel, müşteri ve ödemeleri tek panelde yönetmeye yarayan pratik, bulut tabanlı araçlar geliştiriyoruz.
            </p>

            <h2>BooKi'nin Sundukları</h2>
            <ul>
                <li>Çift rezervasyonu önleyen online randevu ve takvim yönetimi.</li>
                <li>Giriş/çıkış takibi ile personel ve komisyon yönetimi.</li>
                <li>Müşteri ilişkileri yönetimi ve rezervasyon geçmişi.</li>
                <li>iyzico üzerinden güvenli online ödeme desteği (Visa ve Mastercard).</li>
                <li>Google Takvim dahil üçüncü taraf takvim senkronizasyonu.</li>
                <li>İşletmelerin yeni müşterilere ulaşabildiği halka açık pazaryeri ("RandevuBurada").</li>
            </ul>

            <h2>Taahhüdümüz</h2>
            <p>
                Güvenilirlik, güvenlik ve gizlilik konularında kararlıyız. BooKi, kişisel verileri KVKK (Türkiye) ve GDPR (AB) mevzuatına uygun şekilde işler. Ödeme işlemleri yalnızca PCI-DSS uyumlu ödeme kuruluşu iyzico üzerinden gerçekleştirilir; kart bilgileri sunucularımıza asla ulaşmaz.
            </p>

            <h2>İletişim</h2>
            <p>
                <strong>Ki Software / Ki Business Solutions</strong><br>
                Adres: Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa<br>
                Web Sitesi: <a href="https://kibusiness.co" target="_blank" rel="noopener">https://kibusiness.co</a><br>
                E-posta: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a><br>
                Hukuki İşler: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>
            </p>
        </article>

    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi" style="margin-bottom:0.75rem;">
                    <span class="brand__logo">BOO·KI</span>
                </a>
                <p style="margin-top:0.75rem;max-width:22rem;line-height:1.6;">
                    Randevu, takvim, müşteri ve ödeme yönetimi tek panelde. Ki Software tarafından geliştirilmiştir.
                </p>
            </div>
            <div>
                <h4>Ürün / Product</h4>
                <ul>
                    <li><a href="<?= base_url('/#ozellikler') ?>">Özellikler</a></li>
                    <li><a href="<?= base_url('marketplace') ?>">Marketplace</a></li>
                    <li><a href="<?= e(vars('portal_url')) ?>">Giriş Yap</a></li>
                </ul>
            </div>
            <div>
                <h4>Yasal / Legal</h4>
                <ul>
                    <li><a href="<?= base_url('about') ?>">About Us / Hakkımızda</a></li>
                    <li><a href="<?= base_url('privacy') ?>">Privacy Policy / Gizlilik</a></li>
                    <li><a href="<?= base_url('terms') ?>">Terms of Service / Şartlar</a></li>
                    <li><a href="<?= base_url('mesafeli-satis') ?>">Mesafeli Satış Sözleşmesi</a></li>
                    <li><a href="<?= base_url('teslimat-iade') ?>">Teslimat &amp; İade</a></li>
                </ul>
            </div>
            <div>
                <h4>Şirket / Company</h4>
                <ul>
                    <li><a href="https://kibusiness.co" target="_blank" rel="noopener">Ki Business Solutions</a></li>
                    <li><a href="https://software.kibusiness.co" target="_blank" rel="noopener">Ki Software</a></li>
                </ul>
            </div>
        </div>
        <div class="payment-row">
            <span>Güvenli Ödeme</span>
            <img src="<?= asset_url('assets/img/iyzico/footer_iyzico_ile_ode.svg') ?>" alt="iyzico ile Öde" style="height:28px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/visa.svg') ?>" alt="Visa" style="height:24px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/mastercard.svg') ?>" alt="Mastercard" style="height:26px;width:auto;" loading="lazy">
            <img src="<?= asset_url('assets/img/iyzico/troy.svg') ?>" alt="Troy" style="height:24px;width:auto;border-radius:4px;" loading="lazy">
        </div>
        <div class="site-footer__bottom">
            <div>© <?= date('Y') ?> BooKi · Ki Software (Ki Business Solutions). Tüm hakları saklıdır.</div>
            <div><a href="<?= base_url('privacy') ?>" style="color:#cbd5e1;">Privacy</a> · <a href="<?= base_url('terms') ?>" style="color:#cbd5e1;">Terms</a> · <a href="<?= base_url('mesafeli-satis') ?>" style="color:#cbd5e1;">Mesafeli Satış</a> · <a href="<?= base_url('teslimat-iade') ?>" style="color:#cbd5e1;">İade</a></div>
        </div>
    </div>
</footer>

<script>
function switchLanguage(lang) {
    const enBlock = document.getElementById('aboutEnglish');
    const trBlock = document.getElementById('aboutTurkish');
    const btnEn = document.getElementById('btnEn');
    const btnTr = document.getElementById('btnTr');
    const heroTitle = document.getElementById('heroTitle');
    const heroSubtitle = document.getElementById('heroSubtitle');

    if (lang === 'tr') {
        enBlock.style.display = 'none';
        trBlock.style.display = 'block';
        btnEn.classList.remove('active');
        btnTr.classList.add('active');
        heroTitle.innerText = 'Hakkımızda';
        heroSubtitle.innerText = 'Hizmet işletmeleri için online randevu ve işletme yönetim platformu.';
        document.documentElement.lang = 'tr';
    } else {
        enBlock.style.display = 'block';
        trBlock.style.display = 'none';
        btnTr.classList.remove('active');
        btnEn.classList.add('active');
        heroTitle.innerText = 'About BooKi';
        heroSubtitle.innerText = 'The online appointment scheduling and business management platform for service-oriented businesses.';
        document.documentElement.lang = 'en';
    }
}
</script>

</body>
</html>