<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="Mesafeli Satış Sözleşmesi — BooKi online randevu ve salon yönetim platformu ile iyzico üzerinden yapılan ödemeleri kapsayan mesafeli satış sözleşmesi. Distance Sales Contract for BooKi.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('mesafeli-satis') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BooKi — Mesafeli Satış Sözleşmesi / Distance Sales Contract">
    <meta property="og:description" content="Distance Sales Contract for BooKi by Ki Software.">
    <meta property="og:url" content="<?= base_url('mesafeli-satis') ?>">
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
        <span class="hero-badge">📄 Mesafeli Satış Sözleşmesi</span>
        <h1 id="heroTitle">Mesafeli Satış Sözleşmesi</h1>
        <p id="heroSubtitle">This Distance Sales Contract ("Sözleşme") governs purchases of BooKi services made at a distance (online).</p>
        <div class="meta-strip">
            <div><strong>Version:</strong> 1.0</div>
            <div><strong>Effective Date:</strong> <?= date('d.m.Y') ?></div>
            <div><strong>Seller:</strong> Ki Software / Ki Business Solutions</div>
        </div>
    </div>
</section>

<main class="policy-content">
    <div class="container">

        <!-- ================= TURKISH SECTION ================= -->
        <article class="policy-card" id="mesafeliTurkish" style="display:none;">
            <h2>1. Taraflar</h2>
            <p>
                Bu Mesafeli Satış Sözleşmesi, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, işbu sözleşme metninin BooKi platformu (booki.kibusiness.co ve alt alan adları) üzerinden onaylanması ile kurulur.
            </p>
            <ul>
                <li><strong>SATICI:</strong> Ki Software / Ki Business Solutions — web: <a href="https://software.kibusiness.co" target="_blank" rel="noopener">software.kibusiness.co</a>, e-posta: <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a></li>
                <li><strong>ALICI / TÜKETİCİ:</strong> BooKi platformuna kayıt olan ve işbu sözleşmeyi onaylayarak mesafeli satış işlemine taraf olan gerçek veya tüzel kişi.</li>
            </ul>

            <h2>2. Sözleşmenin Konusu ve Kapsamı</h2>
            <p>
                Bu sözleşme, ALICI tarafından SATICI'nın sağladığı ve aşağıda nitelik, miktar, süre ve kapsamı belirtilen BooKi abonelik ve hizmetlerinin ("HİZMET") satın alınması, ödenmesi, kullanım şartları ve bu HİZMET karşılığı iyzico sanal POS üzerinden alınacak bedelin ödenmesi ile ilgili hükümleri kapsar.
            </p>
            <div class="highlight-box">
                <h4>HİZMET Kapsamı</h4>
                <p>Online randevu planlama ve takvim yönetimi, personel ve komisyon yönetimi, müşteri yönetimi, Google Takvim senkronizasyonu, marketplace listeleme ve iyzico üzerinden online ödeme alma özelliklerini içerir.</p>
            </div>

            <h2>3. Sözleşme Tarihi, Ödeme ve Teslim</h2>
            <ul>
                <li><strong>Sözleşme Tarihi:</strong> ALICI'nın ödeme adımında sözleşmeyi onayladığı tarihtir.</li>
                <li><strong>Ödeme:</strong> Ödeme, iyzico sanal POS altyapısı üzerinden kredi/banka kartı (Visa, Mastercard) veya banka havalesi ile yapılır. Kart bilgileri iyzico tarafından güvenli ortamda işlenir; SATICI tarafından saklanmaz.</li>
                <li><strong>Teslim / Aktivasyon:</strong> HİZMET dijital bir hizmettir; fiziki teslimat yapılmaz. Ödeme onayını takiben abonelik hesaba anında tanımlanır ve aktivasyon bilgileri e-posta ile iletilir.</li>
            </ul>

            <h2>4. Cayma Hakkı</h2>
            <p>
                ALICI, 6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, sözleşmenin kurulduğu tarihten itibaren <strong>on dört (14) gün</strong> içinde hiçbir gerekçe göstermeksizin ve cezai şart ödemeksizin sözleşmeden cayma hakkına sahiptir.
            </p>
            <div class="highlight-box">
                <h4>Teslim Edilmeye Hazır Dijital İçerik İstisnası</h4>
                <p>
                    Mesafeli Sözleşmeler Yönetmeliği md. 15(1)(ğ) uyarınca, ALICI'nın açık onayı ile ifasına başlanan ve ifası tamamlanan abonelik/üyelik sözleşmeleri ("elektronik ortamda anında ifa edilen hizmetler") cayma hakkının istisnasıdır. BooKi HİZMETİ, aktivasyon anında anında ifa edildiğinden, aboneliğin aktifleştirilmesi sonrası cayma hakkı bulunmamaktadır. Aktivasyonla birlikte tahsil edilen bedel iade edilmez.
                </p>
            </div>

            <h2>5. Cayma Bildirimi ve İade (Cayma Hakkı Olan Durumlarda)</h2>
            <p>
                Süresi içinde cayma hakkı bulunan hallerde ALICI, cayma bildirimini SATICI e-posta adresine (<a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a>) yazılı olarak ya da booKi platformundaki ilgili sekme üzerinden iletmelidir.
            </p>
            <ol>
                <li>SATICI, cayma bildiriminin kendisine ulaştığı tarihten itibaren en geç <strong>14 gün</strong> içinde alınan bedeli, kullanılan HİZMET oranında iade eder.</li>
                <li>İade, ödemenin yapıldığı iyzico üzerinden orijinal kart/hesaba yapılır; buna ilişkin ek bilgi için <a href="<?= base_url('teslimat-iade') ?>">Teslimat &amp; İade Politika</a> sayfası incelenmelidir.</li>
            </ol>

            <h2>6. Hizmetin Yürütülmesi ve Tüketici Talepleri</h2>
            <ul>
                <li>HİZMET'in sürekliliği için yasal gerekliliklere uygun bakım ve güvenlik tedbirleri uygulanır.</li>
                <li>Platformda planlı veya zorunlu bakım nedeniyle kısa süreli kesinti olabilir; bu durumlar önceden duyurulur.</li>
                <li>Tüketici talepleri <a href="mailto:support@kibusiness.co">support@kibusiness.co</a> üzerinden değerlendirilir.</li>
            </ul>

            <h2>7. Uygulanacak Hukuk ve Uyuşmazlık Çözümü</h2>
            <p>
                Bu sözleşmeden doğan uyuşmazlıklarda Müşteri Hakem Heyetleri ve tüketici mahkemeleri yetkilidir. SATICI'nın yerleşim yeri dikkate alınarak Tüketicinin Korunması Hakkında Kanun md. 68 uyarınca belirlenen tüketici hakem heyetleri, parasal sınırlar dahilinde zorunlu başvuru merciidir. Sözleşmenin kurulması ve ifasında Türkiye Cumhuriyeti kanunları uygulanır.
            </p>
        </article>

        <!-- ================= ENGLISH SECTION ================= -->
        <article class="policy-card" id="mesafeliEnglish">
            <h2>1. Parties</h2>
            <p>
                This Distance Sales Contract is concluded in accordance with Turkish Law No. 6502 on the Protection of Consumers and the Regulation on Distance Contracts, upon acceptance of this contract text on the BooKi platform (booki.kibusiness.co and its subdomains).
            </p>
            <ul>
                <li><strong>SELLER:</strong> Ki Software / Ki Business Solutions — web: <a href="https://software.kibusiness.co" target="_blank" rel="noopener">software.kibusiness.co</a>, email: <a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a></li>
                <li><strong>BUYER / CONSUMER:</strong> The natural or legal person who registers on the BooKi platform and accepts this contract to become a party to the distance sale transaction.</li>
            </ul>

            <h2>2. Subject of the Contract and Scope</h2>
            <p>
                This contract covers the purchase, payment, usage terms of the BooKi subscription and related services ("SERVICE") supplied by the SELLER to the BUYER, and the payment of the price collected through the iyzico virtual POS for the SERVICE.
            </p>
            <div class="highlight-box">
                <h4>Scope of the SERVICE</h4>
                <p>Online appointment scheduling and calendar management, staff and commission management, customer management, Google Calendar synchronization, marketplace listing and online payment collection via iyzico.</p>
            </div>

            <h2>3. Contract Date, Payment and Delivery</h2>
            <ul>
                <li><strong>Contract Date:</strong> The date the BUYER approves the contract at the payment step.</li>
                <li><strong>Payment:</strong> Payment is made through the iyzico virtual POS infrastructure via credit/debit card (Visa, Mastercard) or bank transfer. Card details are processed securely by iyzico and are never stored by the SELLER.</li>
                <li><strong>Delivery / Activation:</strong> The SERVICE is a digital service; no physical delivery takes place. Following payment approval, the subscription is assigned to the account immediately and activation details are sent by email.</li>
            </ul>

            <h2>4. Right of Withdrawal</h2>
            <p>
                Under Law No. 6502 and the Regulation on Distance Contracts, the BUYER has the right to withdraw from the contract <strong>within fourteen (14) days</strong> from the date the contract is concluded, without giving any reason and without penalty.
            </p>
            <div class="highlight-box">
                <h4>Digital Content Exemption</h4>
                <p>
                    Under Art. 15(1)(ğ) of the Regulation on Distance Contracts, subscription/membership contracts whose performance has started (and been completed) with the BUYER's express consent — i.e. services performed immediately in an electronic environment — are exempt from the right of withdrawal. Because the BooKi SERVICE is performed immediately upon activation, no right of withdrawal exists after the subscription is activated, and the collected price is not refunded.
                </p>
            </div>

            <h2>5. Withdrawal Notice and Refund (Where Withdrawal Applies)</h2>
            <p>
                In cases where the right of withdrawal still applies, the BUYER must send the withdrawal notice in writing to the SELLER's email address (<a href="mailto:finance@kibusiness.co">finance@kibusiness.co</a>) or via the relevant section of the BooKi platform.
            </p>
            <ol>
                <li>The SELLER shall refund the collected price, pro-rated to the SERVICE used, no later than <strong>14 days</strong> from the date the withdrawal notice is received.</li>
                <li>Refunds are made via iyzico to the original card/account used for payment; see the <a href="<?= base_url('teslimat-iade') ?>">Delivery &amp; Returns Policy</a> page for details.</li>
            </ol>

            <h2>6. Service Operation and Consumer Requests</h2>
            <ul>
                <li>Maintenance and security measures required by law are applied to maintain continuity of the SERVICE.</li>
                <li>Short scheduled or emergency maintenance interruptions may occur and will be announced in advance.</li>
                <li>Consumer requests are handled via <a href="mailto:support@kibusiness.co">support@kibusiness.co</a>.</li>
            </ul>

            <h2>7. Applicable Law and Dispute Resolution</h2>
            <p>
                In disputes arising from this contract, Consumer Arbitration Committees and consumer courts are competent. Within the monetary limits set by Art. 68 of Law No. 6502, the relevant Consumer Arbitration Committee is the mandatory first instance. Turkish law applies to the formation and performance of this contract.
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
    const enBlock = document.getElementById('mesafeliEnglish');
    const trBlock = document.getElementById('mesafeliTurkish');
    const btnEn = document.getElementById('btnEn');
    const btnTr = document.getElementById('btnTr');
    const heroTitle = document.getElementById('heroTitle');
    const heroSubtitle = document.getElementById('heroSubtitle');

    if (lang === 'tr') {
        enBlock.style.display = 'none';
        trBlock.style.display = 'block';
        btnEn.classList.remove('active');
        btnTr.classList.add('active');
        heroTitle.innerText = 'Mesafeli Satış Sözleşmesi';
        heroSubtitle.innerText = 'BooKi hizmetlerinin mesafeli (online) satışını düzenleyen sözleşme.';
        document.documentElement.lang = 'tr';
    } else {
        enBlock.style.display = 'block';
        trBlock.style.display = 'none';
        btnTr.classList.remove('active');
        btnEn.classList.add('active');
        heroTitle.innerText = 'Distance Sales Contract';
        heroSubtitle.innerText = 'This Distance Sales Contract ("Sözleşme") governs purchases of BooKi services made at a distance (online).';
        document.documentElement.lang = 'en';
    }
}
</script>

</body>
</html>