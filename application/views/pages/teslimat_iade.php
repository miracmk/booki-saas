<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="Teslimat ve İade Politikası — BooKi hizmetlerinin teslimat (aktivasyon), iptal ve iyzico üzerinden iade süreçleri. Delivery & Returns Policy for BooKi by Ki Software.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('teslimat-iade') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BooKi — Teslimat ve İade / Delivery & Returns">
    <meta property="og:description" content="Delivery and Returns Policy for BooKi by Ki Software.">
    <meta property="og:url" content="<?= base_url('teslimat-iade') ?>">
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
        <span class="hero-badge">🚚 Teslimat &amp; İade</span>
        <h1 id="heroTitle">Teslimat ve İade Politikası</h1>
        <p id="heroSubtitle">This policy explains how BooKi digital services are delivered (activated) and how cancellations and refunds are handled through iyzico.</p>
        <div class="meta-strip">
            <div><strong>Version:</strong> 1.0</div>
            <div><strong>Effective Date:</strong> <?= date('d.m.Y') ?></div>
            <div><strong>Payment Provider:</strong> iyzico (Visa · Mastercard)</div>
        </div>
    </div>
</section>

<main class="policy-content">
    <div class="container">

        <!-- ================= TURKISH SECTION ================= -->
        <article class="policy-card" id="teslimatTurkish" style="display:none;">
            <h2>1. Teslimat (Hizmet Aktivasyonu)</h2>
            <p>
                BooKi platformu üzerinden satın alınan tüm hizmetler <strong>dijital hizmet</strong> niteliğindedir; bu nedenle fiziki ürün teslimatı yapılmaz.
            </p>
            <ul>
                <li>Online ödeme tutarı, iyzico sanal POS tarafından onaylandığı anda ödeme kesinleşir.</li>
                <li>Ödeme onayını takiben HİZMET, alıcının hesabına <strong>anında tanımlanır ve aktive edilir</strong>.</li>
                <li>Aktivasyon ve giriş bilgilerini içeren onay e-postası, ödeme sonrasında alıcının kayıtlı e-posta adresine iletilir.</li>
                <li>HİZMET'e ilişkin kullanım kılavuzu ve destek, <a href="mailto:support@kibusiness.co">support@kibusiness.co</a> üzerinden sağlanır.</li>
            </ul>

            <h2>2. Cayma ve İptal</h2>
            <p>
                Dijital abonelik hizmetleri, aktivasyon anında anında ifa edilen hizmetler kapsamında olduğundan <strong>cayma hakkının istisnası</strong>dır (bkz. <a href="<?= base_url('mesafeli-satis') ?>">Mesafeli Satış Sözleşmesi</a> md. 4). Aşağıdaki durumlarda iade süreci uygulanır:
            </p>
            <ul>
                <li><strong>Yanlışlıkla/Sahtekarlıkla yapılan ödeme:</strong> Nakit yaklaşımıyla, ödemenin doğrulanması sonrasında iade edilir.</li>
                <li><strong>Hizmetin hiç aktive edilmemesi:</strong> Ödeme onayına rağmen hesap aktivasyonunun gerçekleştirilmediği teyit edilirse tutar iade edilir.</li>
                <li><strong>Yasal olarak cayma hakkının doğduğu istisnai durumlar:</strong> 14 günlük cayma süresi içinde, hizmetin ifasına başlanmamış olması şartıyla.</li>
            </ul>

            <h2>3. İade Süreci (iyzico Üzerinden)</h2>
            <ol>
                <li>İade talebi, <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> adresine yazılı olarak veya platformdaki ilgili bölümden iletilir.</li>
                <li>İade onayı sonrasında tutar, ödemenin yapıldığı iyzico işlemine iade edilir.</li>
                <li>İadenin banka/cari hesaba yansıması, ödemenin yapıldığı kartın/banka kuruluşunun sürelerine bağlı olarak <strong>genellikle 5-10 iş günü</strong> içinde gerçekleşir.</li>
                <li>İade durumunda, ilgili abonelik/hizmet kullanımı aynı anda sonlandırılır/iptal edilir.</li>
            </ol>

            <div class="highlight-box">
                <h4>Önemli Not</h4>
                <p>
                    iyzico, bankacılık mevzuatı gereği kart iadelerini yalnızca ödemenin yapıldığı kart/hesaba yapabilir. Farklı bir hesaba transfer talepleri kabul edilemez.
                </p>
            </div>

            <h2>4. Randevu Bazlı Rezervasyonlarda İade (Pazaryeri)</h2>
            <p>
                RandevuBurada pazaryeri üzerinden yapılan randevu ödemelerinde iade; işletmenin (satıcının) kendi iptal/koşul politikasına ve randevu durumuna göre uygulanır. İade koşulları, ödeme öncesinde işletme detay sayfasında gösterilir; rezervasyona ilişkin sorunlarda ilk muhatap işletmedir.
            </p>

            <h2>5. Destek ve Uyuşmazlık</h2>
            <p>
                İade süreciyle ilgili her türlü soru için <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> ile iletişime geçilebilir. Uyuşmazlık halinde <a href="<?= base_url('mesafeli-satis') ?>">Mesafeli Satış Sözleşmesi</a> md. 7'deki hakem heyeti/tüketici mahkemesi hükümleri geçerlidir.
            </p>
        </article>

        <!-- ================= ENGLISH SECTION ================= -->
        <article class="policy-card" id="teslimatEnglish">
            <h2>1. Delivery (Service Activation)</h2>
            <p>
                All services purchased on the BooKi platform are <strong>digital services</strong>; no physical product delivery takes place.
            </p>
            <ul>
                <li>As soon as the online payment amount is approved by the iyzico virtual POS, the payment is confirmed.</li>
                <li>Following payment approval, the SERVICE is <strong>immediately assigned and activated</strong> on the buyer's account.</li>
                <li>A confirmation email containing activation and login details is sent to the buyer's registered email address after payment.</li>
                <li>User guides and support are provided via <a href="mailto:support@kibusiness.co">support@kibusiness.co</a>.</li>
            </ul>

            <h2>2. Withdrawal and Cancellation</h2>
            <p>
                Digital subscription services are exempt from the right of withdrawal as services performed immediately upon activation (see <a href="<?= base_url('mesafeli-satis') ?>">Distance Sales Contract</a>, Art. 4). The refund process applies in the following cases:
            </p>
            <ul>
                <li><strong>Mistaken/Fraudulent payment:</strong> Refunded after verification of the charge.</li>
                <li><strong>Service never activated:</strong> If it is confirmed that account activation did not occur despite payment approval, the amount is refunded.</li>
                <li><strong>Exceptional cases where withdrawal legally applies:</strong> Within the 14-day withdrawal period, provided performance of the service has not started.</li>
            </ul>

            <h2>3. Refund Process (via iyzico)</h2>
            <ol>
                <li>A refund request is sent in writing to <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a> or via the relevant section of the platform.</li>
                <li>After refund approval, the amount is reversed to the original iyzico transaction.</li>
                <li>Depending on the issuing bank/card network, the refund is usually reflected <strong>within 5-10 business days</strong>.</li>
                <li>Upon refund, the related subscription/service is terminated/cancelled at the same time.</li>
            </ol>

            <div class="highlight-box">
                <h4>Important Note</h4>
                <p>
                    Due to banking regulations, iyzico can only make card refunds to the card/account used for the original payment. Transfer requests to a different account cannot be accepted.
                </p>
            </div>

            <h2>4. Refunds for Booking-Based Reservations (Marketplace)</h2>
            <p>
                For appointment payments made through the RandevuBurada marketplace, refunds are applied according to the business (seller)'s own cancellation/terms policy and the appointment status. Refund conditions are shown on the business detail page before payment; for issues related to a reservation, the business is the first point of contact.
            </p>

            <h2>5. Support and Disputes</h2>
            <p>
                For any questions about the refund process, contact <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a>. In case of a dispute, the arbitration committee / consumer court provisions in <a href="<?= base_url('mesafeli-satis') ?>">Distance Sales Contract</a> Art. 7 apply.
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
        </div>
        <div class="site-footer__bottom">
            <div>© <?= date('Y') ?> BooKi · Ki Software (Ki Business Solutions). Tüm hakları saklıdır.</div>
            <div><a href="<?= base_url('privacy') ?>" style="color:#cbd5e1;">Privacy</a> · <a href="<?= base_url('terms') ?>" style="color:#cbd5e1;">Terms</a> · <a href="<?= base_url('mesafeli-satis') ?>" style="color:#cbd5e1;">Mesafeli Satış</a> · <a href="<?= base_url('teslimat-iade') ?>" style="color:#cbd5e1;">İade</a></div>
        </div>
    </div>
</footer>

<script>
function switchLanguage(lang) {
    const enBlock = document.getElementById('teslimatEnglish');
    const trBlock = document.getElementById('teslimatTurkish');
    const btnEn = document.getElementById('btnEn');
    const btnTr = document.getElementById('btnTr');
    const heroTitle = document.getElementById('heroTitle');
    const heroSubtitle = document.getElementById('heroSubtitle');

    if (lang === 'tr') {
        enBlock.style.display = 'none';
        trBlock.style.display = 'block';
        btnEn.classList.remove('active');
        btnTr.classList.add('active');
        heroTitle.innerText = 'Teslimat ve İade Politikası';
        heroSubtitle.innerText = 'BooKi dijital hizmetlerinin teslimatı (aktivasyon) ve iyzico üzerinden iptal/iade süreçleri.';
        document.documentElement.lang = 'tr';
    } else {
        enBlock.style.display = 'block';
        trBlock.style.display = 'none';
        btnTr.classList.remove('active');
        btnEn.classList.add('active');
        heroTitle.innerText = 'Delivery and Returns Policy';
        heroSubtitle.innerText = 'This policy explains how BooKi digital services are delivered (activated) and how cancellations and refunds are handled through iyzico.';
        document.documentElement.lang = 'en';
    }
}
</script>

</body>
</html>