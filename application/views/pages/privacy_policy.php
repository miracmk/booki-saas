<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="Privacy Policy for BooKi Appointment & Booking Platform by Ki Software. Learn how we access, use, protect, retain, and delete Google user data and personal information.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('privacy') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BooKi — Privacy Policy">
    <meta property="og:description" content="Privacy Policy and Google API Services User Data Policy Compliance for BooKi by Ki Software.">
    <meta property="og:url" content="<?= base_url('privacy') ?>">
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

        /* ---------- Policy Content ---------- */
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

        /* Highlight box for Google OAuth */
        .compliance-callout {
            background: #f0fdf4;
            border-left: 4px solid #16a34a;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin: 1.5rem 0;
        }
        .compliance-callout h4 {
            color: #15803d;
            font-size: 1.05rem;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .compliance-callout p {
            margin-bottom: 0.5rem;
            color: #166534;
            font-size: 0.94rem;
        }
        .compliance-callout p:last-child { margin-bottom: 0; }

        .warning-callout {
            background: #fffbeb;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin: 1.5rem 0;
        }
        .warning-callout h4 {
            color: #b45309;
            font-size: 1.05rem;
            margin-bottom: 0.4rem;
        }
        .warning-callout p {
            color: #92400e;
            font-size: 0.94rem;
            margin-bottom: 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.25rem 0;
            font-size: 0.92rem;
        }
        .data-table th, .data-table td {
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }
        .data-table th {
            background: #f8fafc;
            color: var(--navy);
            font-weight: 700;
        }

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
        <span class="hero-badge">🔒 Privacy &amp; Data Protection</span>
        <h1 id="heroTitle">BooKi Privacy Policy</h1>
        <p id="heroSubtitle">Comprehensive disclosure of our data practices, user rights, and strict compliance with the Google API Services User Data Policy.</p>
        <div class="meta-strip">
            <div><strong>Application:</strong> BooKi Appointment &amp; Booking Platform</div>
            <div><strong>Developer / Entity:</strong> Ki Software / Ki Business Solutions</div>
            <div><strong>Effective Date:</strong> September 19, 2026</div>
            <div><strong>Dedicated URL:</strong> <a href="https://booki.kibusiness.co/privacy">booki.kibusiness.co/privacy</a></div>
        </div>
    </div>
</section>

<main class="policy-content">
    <div class="container">

        <!-- ================= ENGLISH SECTION ================= -->
        <article class="policy-card" id="policyEnglish">
            <h2>1. Introduction &amp; Ownership</h2>
            <p>
                This Privacy Policy describes how <strong>BooKi</strong> ("BooKi", "Application", "we", "us", or "our"), an online appointment scheduling and business management platform developed and operated by <strong>Ki Software</strong> (a division of <strong>Ki Business Solutions</strong>, website: <a href="https://kibusiness.co" target="_blank" rel="noopener">kibusiness.co</a>), collects, accesses, uses, stores, protects, and discloses information when you access or use our services via our website (<strong>https://booki.kibusiness.co</strong>) and associated software endpoints (<strong>https://bookiapp.kibusiness.co</strong>).
            </p>
            <p>
                We are firmly committed to protecting your privacy, honoring your trust, and complying with all applicable international data protection standards, including the EU General Data Protection Regulation (GDPR), the Turkish Law on the Protection of Personal Data (KVKK No. 6698), and Google's API Services User Data Policy.
            </p>

            <h2>2. Information We Collect</h2>
            <p>
                We collect personal information that you provide to us directly, information generated automatically through platform usage, and information accessed via third-party integrations when you explicitly authorize them.
            </p>
            <h3>A. Information You Provide Directly</h3>
            <ul>
                <li><strong>Account Registration &amp; Profile:</strong> Full name, business name, phone number, email address, physical address, and password credentials.</li>
                <li><strong>Appointment &amp; Customer Data:</strong> Customer contact details, scheduled service dates, service durations, staff assignments, notes, and pricing information entered to manage reservations.</li>
                <li><strong>Customer Inquiries &amp; Support:</strong> Communications and records submitted when requesting technical support or billing assistance.</li>
            </ul>

            <h3>B. Automatically Collected Technical Data</h3>
            <ul>
                <li>Log details, browser type, device information, IP address, request timestamps, and session identifiers collected strictly to maintain security, prevent abuse, and guarantee system performance.</li>
            </ul>

            <h2>3. Google User Data Accessed, Used &amp; Disclosed</h2>
            <p>
                BooKi provides seamless integration with Google APIs (notably <strong>Google Calendar</strong>) to allow business owners, staff providers, and customers to coordinate schedules without conflicts. When you connect your Google Account to BooKi, our application requests access to specific Google user data under your explicit consent.
            </p>

            <div class="compliance-callout">
                <h4>🛡️ Google API Services User Data Policy Compliance</h4>
                <p>
                    BooKi's use and transfer to any other app of information received from Google APIs adheres to the 
                    <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener"><strong>Google API Services User Data Policy</strong></a>, including the <strong>Limited Use requirements</strong>.
                </p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Google OAuth Scope</th>
                        <th>Google User Data Accessed</th>
                        <th>Purpose &amp; Specific Use</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <code>openid</code><br>
                            <code>.../auth/userinfo.email</code>
                        </td>
                        <td>
                            Google User ID, primary email address, and profile name.
                        </td>
                        <td>
                            To authenticate your identity, securely sign you into your BooKi account, and verify ownership of the connected Google Account.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/calendar</code>
                        </td>
                        <td>
                            Calendar titles, event summaries, event descriptions, start and end dates/times, attendee status, and calendar IDs.
                        </td>
                        <td>
                            <strong>Two-way appointment synchronization:</strong><br>
                            1. Inserting confirmed BooKi appointments directly into your selected Google Calendar.<br>
                            2. Reading existing busy slots on your Google Calendar to block unavailable times in BooKi, preventing double-bookings.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/contacts</code><br>
                            <em>(Optional Admin Add-on)</em>
                        </td>
                        <td>
                            Customer names, phone numbers, and email addresses.
                        </td>
                        <td>
                            Optionally synchronizing client contacts into the provider's directory to streamline appointment reminders, strictly when explicitly enabled by the administrator.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/spreadsheets</code><br>
                            <code>.../auth/drive.file</code><br>
                            <em>(Optional Admin Add-on)</em>
                        </td>
                        <td>
                            Spreadsheet content and user-designated files created or selected by BooKi.
                        </td>
                        <td>
                            Exporting appointment registries and revenue reports into a Google Sheet chosen by the user for internal business bookkeeping.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/tasks</code><br>
                            <em>(Optional Admin Add-on)</em>
                        </td>
                        <td>
                            Task titles, due dates, and task lists.
                        </td>
                        <td>
                            Creating actionable reminders on the provider's Google Tasks list for upcoming appointments.
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2>4. Prohibited Uses of Google User Data</h2>
            <p>
                In strict accordance with Google Cloud verification and OAuth data policies, we explicitly guarantee and disclose that:
            </p>
            <ul>
                <li><strong>No Sale of Data:</strong> We will <strong>never sell, lease, trade, or rent</strong> Google user data or customer information to third parties, data brokers, or advertisers under any circumstances.</li>
                <li><strong>No Targeted or Interest-Based Advertising:</strong> We do <strong>not use or transfer</strong> Google user data for serving advertisements, including personalized, re-targeted, or interest-based advertising.</li>
                <li><strong>No Creditworthiness or Lending Determinations:</strong> Google user data is never accessed or used to determine creditworthiness or for credit/lending scoring.</li>
                <li><strong>Strict Feature Limitation:</strong> Our use of Google user data is strictly limited to providing and improving the user-facing appointment booking and calendar synchronization functionality of BooKi.</li>
            </ul>

            <div class="warning-callout">
                <h4>🤖 Mandatory AI / Machine Learning Affirmation</h4>
                <p>
                    <strong>Google Workspace APIs and Google user data are NEVER used to develop, improve, or train generalized, non-personalized artificial intelligence (AI) and/or machine learning (ML) models.</strong>
                </p>
            </div>

            <h2>5. Data Sharing, Transfer &amp; Disclosure</h2>
            <p>
                We do not transfer or disclose your Google user data to third parties for purposes other than the ones explicitly provided in this policy. We may only disclose data in the following strictly limited situations:
            </p>
            <ul>
                <li><strong>Authorized Service Providers:</strong> Secure cloud hosting, database, and infrastructure providers who process data strictly on our behalf under binding confidentiality agreements and ISO 27001 / SOC 2 certifications.</li>
                <li><strong>Legal Compliance:</strong> When strictly required to comply with a valid court order, subpoena, or applicable statutory obligation.</li>
            </ul>

            <h2>6. Data Security &amp; Protection Mechanisms</h2>
            <p>
                We implement comprehensive technical and organizational safeguards to ensure the confidentiality, integrity, and availability of Google user data:
            </p>
            <ul>
                <li><strong>Encryption in Transit:</strong> All communications between your browser, our application, and Google API servers are encrypted using modern Transport Layer Security (TLS 1.3 / HTTPS).</li>
                <li><strong>Encryption at Rest:</strong> All stored OAuth credentials, access tokens, refresh tokens, and personally identifiable information (PII) are encrypted at rest using <strong>AES-256-GCM</strong> (Galois/Counter Mode) with cryptographic key separation.</li>
                <li><strong>Cryptographic Verification:</strong> Field-level indexing uses <strong>HMAC-SHA256</strong> to permit exact search without exposing plain-text keys or raw PII.</li>
                <li><strong>Access Control:</strong> Strict Role-Based Access Control (RBAC) ensures only authorized account owners can view their respective appointment data.</li>
            </ul>

            <h2>7. Data Retention &amp; Right to Deletion</h2>
            <p>
                We store Google user data only for the period necessary to provide the services requested, or until you choose to disconnect the integration or close your account.
            </p>
            <h3>How to Disconnect Google Integration &amp; Delete Data</h3>
            <ul>
                <li>
                    <strong>From the BooKi Application:</strong> Navigate to <strong>Settings &gt; Integrations &gt; Google Calendar</strong> and click <strong>"Disconnect"</strong>. This action immediately and permanently purges your stored OAuth tokens and ceases all calendar synchronization.
                </li>
                <li>
                    <strong>From Google Account Settings:</strong> You can revoke BooKi's access at any time via your Google Account Permissions portal at 
                    <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">https://myaccount.google.com/permissions</a>.
                </li>
                <li>
                    <strong>Right to Erasure (Complete Account Deletion):</strong> You can request full erasure of your account, appointments, and all associated personal data by visiting our automated privacy portal or emailing our data protection officer at <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>. We fulfill all valid deletion requests within 30 days.
                </li>
            </ul>

            <h2>8. Contact Information</h2>
            <p>
                If you have any questions, concerns, or requests regarding this Privacy Policy or our data handling practices, please contact us:
            </p>
            <p>
                <strong>Ki Software / Ki Business Solutions</strong><br>
                Website: <a href="https://kibusiness.co" target="_blank" rel="noopener">https://kibusiness.co</a><br>
                App Website: <a href="https://booki.kibusiness.co">https://booki.kibusiness.co</a><br>
                Privacy Inquiries: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a><br>
                Technical Support: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a>
            </p>
        </article>

        <!-- ================= TURKISH SECTION ================= -->
        <article class="policy-card" id="policyTurkish" style="display:none;">
            <h2>1. Giriş ve Şirket Bilgileri</h2>
            <p>
                Bu Gizlilik Politikası, <strong>Ki Business Solutions</strong> bünyesindeki <strong>Ki Software</strong> tarafından geliştirilen ve işletilen <strong>BooKi</strong> ("BooKi", "Uygulama", "biz") online randevu ve salon yönetim platformunun; <strong>https://booki.kibusiness.co</strong> web sitesi ve ilişkili uç noktaları (<strong>https://bookiapp.kibusiness.co</strong>) aracılığıyla toplanan, işlenen, saklanan ve korunan kişisel verilere ilişkin uygulamalarını açıklamaktadır.
            </p>
            <p>
                Kullanıcılarımızın ve müşterilerinin mahremiyetine azami özen göstermekte; 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK), Avrupa Birliği Genel Veri Koruma Tüzüğü (GDPR) ve Google API Hizmetleri Kullanıcı Verileri Politikası'na eksiksiz uyum sağlamaktayız.
            </p>

            <h2>2. Toplanan Bilgiler</h2>
            <p>
                Hizmetlerimizin sunulabilmesi adına tarafınızca doğrudan iletilen, kullanım sırasında otomatik olarak üretilen ve onayınız doğrultusunda üçüncü taraf entegrasyonlarından erişilen veriler işlenmektedir.
            </p>
            <h3>A. Doğrudan Sağlanan Bilgiler</h3>
            <ul>
                <li><strong>Hesap ve Profil Bilgileri:</strong> Ad, soyad, işletme unvanı, telefon numarası, e-posta adresi, adres ve şifreleme parolaları.</li>
                <li><strong>Randevu ve Müşteri Kayıtları:</strong> Müşteri ad-soyad ve telefon bilgileri, rezerve edilen hizmetler, seans süreleri, personel atamaları ve notlar.</li>
                <li><strong>Destek Talepleri:</strong> Müşteri hizmetlerimize iletilen teknik ve operasyonel bildirimler.</li>
            </ul>

            <h3>B. Otomatik Olarak Toplanan Teknik Veriler</h3>
            <ul>
                <li>Platform güvenliği, kötüye kullanımın engellenmesi ve sistem kararlılığının sağlanması amacıyla IP adresleri, tarayıcı türü, erişim zaman damgaları ve oturum çerezleri.</li>
            </ul>

            <h2>3. Google Kullanıcı Verilerine Erişim, Kullanım ve Kapsam</h2>
            <p>
                BooKi, işletmelerin ve hizmet sağlayıcı personelin randevularını kendi Google Takvimleri ile çakışmasız biçimde senkronize edebilmesi amacıyla Google API'leri (özellikle <strong>Google Calendar API</strong>) ile entegre çalışır. Google hesabınızı BooKi'ye bağladığınızda yalnızca açık rızanız dahilinde aşağıdaki verilere erişilir:
            </p>

            <div class="compliance-callout">
                <h4>🛡️ Google API Hizmetleri Kullanıcı Verisi Politikası Uyumluluğu</h4>
                <p>
                    BooKi'nin Google API'lerinden aldığı bilgileri kullanımı ve başka uygulamalara aktarımı, Sınırlı Kullanım (Limited Use) gereksinimleri dahil olmak üzere 
                    <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener"><strong>Google API Services User Data Policy</strong></a> şartlarına tam olarak uygundur.
                </p>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Google OAuth Kapsamı</th>
                        <th>Erişilen Google Kullanıcı Verisi</th>
                        <th>Kullanım Amacı ve İşleme Faaliyeti</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <code>openid</code><br>
                            <code>.../auth/userinfo.email</code>
                        </td>
                        <td>
                            Google Kullanıcı Kimliği, birincil e-posta adresi ve profil adı.
                        </td>
                        <td>
                            Kimliğinizi doğrulamak, BooKi hesabınıza güvenli giriş yapmanızı sağlamak ve bağlanan Google hesabının mülkiyetini teyit etmek.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/calendar</code>
                        </td>
                        <td>
                            Takvim başlıkları, etkinlik açıklamaları, başlangıç-bitiş tarih ve saatleri, katılımcı durumu ve takvim kimlikleri (IDs).
                        </td>
                        <td>
                            <strong>Çift yönlü takvim eşitlemesi:</strong><br>
                            1. BooKi üzerinden alınan randevuların seçtiğiniz Google Takvimi'ne otomatik işlenmesi.<br>
                            2. Google Takviminizdeki mevcut meşgul zaman dilimlerinin okunarak BooKi'de randevuya kapatılması ve çifte randevunun önlenmesi.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/contacts</code><br>
                            <em>(İsteğe Bağlı Eklenti)</em>
                        </td>
                        <td>
                            Müşteri isim, telefon ve e-posta kayıtları.
                        </td>
                        <td>
                            İşletme yöneticisi açıkça aktif ettiğinde, randevu hatırlatmalarının kolaylaştırılması amacıyla rehber eşitlemesi.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <code>.../auth/spreadsheets</code><br>
                            <code>.../auth/drive.file</code><br>
                            <em>(İsteğe Bağlı Eklenti)</em>
                        </td>
                        <td>
                            Kullanıcının belirlediği Google E-Tablo ve dosya içerikleri.
                        </td>
                        <td>
                            Randevu listelerinin ve günlük gelir raporlarının, kullanıcının seçtiği bir Google E-Tablosuna muhasebe amacıyla aktarılması.
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2>4. Google Kullanıcı Verilerine İlişkin Kesin Yasaklar</h2>
            <p>
                Google Cloud doğrulama ilkeleri ve kurumsal veri güvenliği taahhüdümüz gereği:
            </p>
            <ul>
                <li><strong>Veri Satışı Kesinlikle Yasaktır:</strong> Google kullanıcı verileri hiçbir şart altında üçüncü taraflara, veri simsarlarına (data brokers) veya pazarlama şirketlerine <strong>satılmaz, kiralanmaz ve takas edilmez</strong>.</li>
                <li><strong>Hedefli veya İlgi Alanına Dayalı Reklam Yapılmaz:</strong> Google kullanıcı verileri kişiselleştirilmiş, yeniden hedeflemeli veya ilgi alanına dayalı reklam sunumu amacıyla <strong>kullanılmaz ve aktarılmaz</strong>.</li>
                <li><strong>Kredi Değerlendirmesi Yapılmaz:</strong> Google verileri kredi değerliliği tespiti, borç verme veya finansal puanlama amacıyla işlenemez.</li>
                <li><strong>Kullanıcıya Doğrudan Hizmet Dışı Kullanım Yasaktır:</strong> Veriler yalnızca kullanıcı tarafından talep edilen randevu ve takvim senkronizasyonu özelliklerini çalıştırmak ve geliştirmek amacıyla kullanılır.</li>
            </ul>

            <div class="warning-callout">
                <h4>🤖 Yapay Zeka / Makine Öğrenimi (AI/ML) Taahhüdü</h4>
                <p>
                    <strong>Google Workspace API'leri ve Google üzerinden elde edilen kullanıcı verileri; genel veya kişiselleştirilmemiş yapay zeka (AI) ve makine öğrenimi (ML) modellerini geliştirmek, iyileştirmek veya eğitmek amacıyla KESİNLİKLE KULLANILMAZ.</strong>
                </p>
            </div>

            <h2>5. Verilerin Paylaşımı ve Aktarımı</h2>
            <p>
                Google kullanıcı verileriniz, bu politikada belirtilen randevu hizmetinin sağlanması dışında üçüncü taraflarla paylaşılmaz. Yalnızca aşağıdaki zorunlu hallerde sınırlı aktarım söz konusu olabilir:
            </p>
            <ul>
                <li><strong>Yetkili Altyapı Sağlayıcılar:</strong> Platformun barındırıldığı ISO 27001 / SOC 2 standartlarına sahip güvenli bulut ve sunucu sağlayıcıları (veri işleyen sıfatıyla ve gizlilik sözleşmelerine bağlı olarak).</li>
                <li><strong>Yasal Zorunluluklar:</strong> Yetkili mahkemeler, savcılıklar veya idari makamlarca yürürlükteki mevzuat uyarınca talep edilen bağlayıcı yasal kararlar.</li>
            </ul>

            <h2>6. Veri Güvenliği ve Koruma Standartları</h2>
            <p>
                Verilerinizin gizliliğini ve bütünlüğünü korumak adına gelişmiş teknik önlemler uygulanmaktadır:
            </p>
            <ul>
                <li><strong>Aktarım Sırasında Şifreleme:</strong> Tüm ağ trafiği TLS 1.3 / HTTPS protokolleri üzerinden 256-bit SSL şifrelemeyle korunur.</li>
                <li><strong>Depolama Sırasında Şifreleme (At-Rest):</strong> Google OAuth jetonları, yenileme belirteçleri ve hassas kişisel veriler veri tabanında <strong>AES-256-GCM</strong> şifreleme algoritmasıyla saklanır.</li>
                <li><strong>Kriptografik Doğrulama:</strong> Alan bazlı arama indekslemeleri <strong>HMAC-SHA256</strong> ile şifrelenir; ham veriler açıkta bırakılmaz.</li>
                <li><strong>Yetkilendirme ve İzolasyon:</strong> Rol tabanlı erişim kontrolü (RBAC) ve çoklu kiracı (multi-tenant) veri izolasyonu uygulanır.</li>
            </ul>

            <h2>7. Veri Saklama Süresi ve Silme Hakkı (İmha Yöntemleri)</h2>
            <p>
                Google kullanıcı verileri yalnızca entegrasyonun aktif olduğu ve hizmetin sürdürüldüğü süre boyunca saklanır.
            </p>
            <h3>Google Bağlantısını Kesme ve Verileri Silme Yöntemleri</h3>
            <ul>
                <li>
                    <strong>BooKi Yönetim Panelinden:</strong> <strong>Ayarlar &gt; Entegrasyonlar &gt; Google Takvim</strong> sayfasına giderek <strong>"Bağlantıyı Kes"</strong> butonuna tıklayabilirsiniz. Bu işlem kaydedilmiş OAuth erişim ve yenileme belirteçlerini veri tabanından anında ve kalıcı olarak siler.
                </li>
                <li>
                    <strong>Google Hesap Ayarlarından:</strong> Dilediğiniz her an Google Güvenlik merkezinden (<a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">https://myaccount.google.com/permissions</a>) BooKi'ye verdiğiniz izinleri anında iptal edebilirsiniz.
                </li>
                <li>
                    <strong>Hesap ve Tüm Verilerin Tamamen Silinmesi:</strong> Müşteri gizlilik portalımız üzerinden veya <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a> adresine e-posta göndererek hesabınızın ve tüm randevu kayıtlarınızın kalıcı olarak silinmesini talep edebilirsiniz. Talepleriniz en geç 30 gün içinde yerine getirilir.
                </li>
            </ul>

            <h2>8. İletişim Bilgileri</h2>
            <p>
                Bu Gizlilik Politikası ve veri işleme uygulamalarımızla ilgili her türlü soru ve talepleriniz için bizimle iletişime geçebilirsiniz:
            </p>
            <p>
                <strong>Ki Software / Ki Business Solutions</strong><br>
                Kurumsal Web Sitesi: <a href="https://kibusiness.co" target="_blank" rel="noopener">https://kibusiness.co</a><br>
                Uygulama Web Sitesi: <a href="https://booki.kibusiness.co">https://booki.kibusiness.co</a><br>
                Gizlilik ve Veri Koruma İletişim: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a><br>
                Teknik Destek: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a>
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
                    <li><a href="<?= base_url('privacy') ?>">Privacy Policy / Gizlilik</a></li>
                    <li><a href="<?= base_url('terms') ?>">Terms of Service / Şartlar</a></li>
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
        <div class="site-footer__bottom">
            <div>© <?= date('Y') ?> BooKi · Ki Software (Ki Business Solutions). Tüm hakları saklıdır.</div>
            <div><a href="<?= base_url('privacy') ?>" style="color:#cbd5e1;">Privacy</a> · <a href="<?= base_url('terms') ?>" style="color:#cbd5e1;">Terms</a></div>
        </div>
    </div>
</footer>

<script>
function switchLanguage(lang) {
    const enBlock = document.getElementById('policyEnglish');
    const trBlock = document.getElementById('policyTurkish');
    const btnEn = document.getElementById('btnEn');
    const btnTr = document.getElementById('btnTr');
    const heroTitle = document.getElementById('heroTitle');
    const heroSubtitle = document.getElementById('heroSubtitle');

    if (lang === 'tr') {
        enBlock.style.display = 'none';
        trBlock.style.display = 'block';
        btnEn.classList.remove('active');
        btnTr.classList.add('active');
        heroTitle.innerText = 'BooKi Gizlilik Politikası';
        heroSubtitle.innerText = 'Veri işleme ilkelerimiz, haklarınız ve Google API Hizmetleri Kullanıcı Verisi Politikası uyumluluk beyanımız.';
        document.documentElement.lang = 'tr';
    } else {
        enBlock.style.display = 'block';
        trBlock.style.display = 'none';
        btnTr.classList.remove('active');
        btnEn.classList.add('active');
        heroTitle.innerText = 'BooKi Privacy Policy';
        heroSubtitle.innerText = 'Comprehensive disclosure of our data practices, user rights, and strict compliance with the Google API Services User Data Policy.';
        document.documentElement.lang = 'en';
    }
}
</script>

</body>
</html>

