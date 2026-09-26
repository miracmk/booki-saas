<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="Terms of Service for BooKi Appointment & Booking Platform by Ki Software. Terms of use, acceptable use policy, and third-party integration terms.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('terms') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BooKi — Terms of Service">
    <meta property="og:description" content="Terms of Service for BooKi by Ki Software.">
    <meta property="og:url" content="<?= base_url('terms') ?>">
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
        <span class="hero-badge">📜 Legal Agreement</span>
        <h1 id="heroTitle">BooKi Terms of Service</h1>
        <p id="heroSubtitle">Please review the terms and conditions governing the use of the BooKi platform and our services.</p>
        <div class="meta-strip">
            <div><strong>Application:</strong> BooKi Appointment &amp; Booking Platform</div>
            <div><strong>Developer / Entity:</strong> Ki Software / Ki Business Solutions</div>
            <div><strong>Effective Date:</strong> September 19, 2026</div>
            <div><strong>Dedicated URL:</strong> <a href="https://booki.kibusiness.co/terms">booki.kibusiness.co/terms</a></div>
        </div>
    </div>
</section>

<main class="policy-content">
    <div class="container">

        <!-- ================= ENGLISH SECTION ================= -->
        <article class="policy-card" id="termsEnglish">
            <h2>1. Agreement to Terms</h2>
            <p>
                These Terms of Service ("Terms") constitute a legally binding agreement between you (whether individually or on behalf of an entity, "User", "you", or "your") and <strong>Ki Software</strong> (a division of <strong>Ki Business Solutions</strong>, operator of <strong>BooKi</strong>, "we", "us", or "our"), governing your access to and use of the <strong>BooKi</strong> appointment scheduling platform, accessible at <strong>https://booki.kibusiness.co</strong> and its related application portals (<strong>https://bookiapp.kibusiness.co</strong>).
            </p>
            <p>
                By creating an account, accessing, or using BooKi, you expressly acknowledge that you have read, understood, and agreed to be bound by these Terms and our <a href="<?= base_url('privacy') ?>">Privacy Policy</a>. If you do not agree to all of these Terms, you are prohibited from using the platform.
            </p>

            <h2>2. Description of the Service</h2>
            <p>
                BooKi is a cloud-based Software-as-a-Service (SaaS) and appointment booking platform designed for service-oriented businesses (including beauty salons, spas, clinics, and studios). Features include online appointment scheduling, calendar coordination, staff commission management, session check-in/check-out tracking, customer relationship management, and third-party calendar synchronizations.
            </p>

            <h2>3. Account Registration &amp; Security</h2>
            <ul>
                <li>You must provide accurate, current, and complete registration information and keep it updated.</li>
                <li>You are solely responsible for maintaining the confidentiality of your account credentials, passwords, and multi-factor authentication devices.</li>
                <li>You agree to notify us immediately of any unauthorized access or security breach involving your account.</li>
                <li>We reserve the right to suspend or terminate accounts that contain false, misleading, or fraudulent information.</li>
            </ul>

            <h2>4. Third-Party Integrations &amp; Google API Services</h2>
            <p>
                BooKi allows users to connect external third-party services, including Google Services (such as Google Calendar), to enable automated appointment synchronization.
            </p>
            <div class="highlight-box">
                <h4>Google API Services Compliance</h4>
                <p>
                    When connecting your Google Account to BooKi:
                </p>
                <ul style="margin-left: 1.25rem; margin-top: 0.5rem;">
                    <li>You grant BooKi permission to access your Google Calendar solely to sync appointment slots and verify availability to prevent double-booking.</li>
                    <li>BooKi's use and transfer to any other app of information received from Google APIs adheres to the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener"><strong>Google API Services User Data Policy</strong></a>, including the Limited Use requirements.</li>
                    <li>You may disconnect or revoke BooKi's Google access at any time from within your BooKi dashboard or via your Google Account settings.</li>
                </ul>
            </div>

            <h2>5. Acceptable Use Policy</h2>
            <p>You agree not to use the BooKi platform to:</p>
            <ul>
                <li>Violate any applicable local, national, or international laws, regulations, or privacy rights (including GDPR and KVKK).</li>
                <li>Transmit unsolicited or unauthorized advertising, promotional materials, or spam.</li>
                <li>Attempt to gain unauthorized access to, probe, scan, or reverse engineer any part of the platform or its connected infrastructure.</li>
                <li>Upload or transmit malware, viruses, malicious code, or disruptive scripts.</li>
                <li>Interfere with, compromise, or disrupt the integrity or security of the platform.</li>
            </ul>

            <h2>6. Intellectual Property Rights</h2>
            <p>
                The platform, including its original source code, architecture, visual design, user interfaces, documentation, logos, and trademarks, is and remains the exclusive property of <strong>Ki Software / Ki Business Solutions</strong> and is protected under international copyright, trademark, and trade secret laws.
            </p>

            <h2>7. Service Availability &amp; Modifications</h2>
            <p>
                We endeavor to provide continuous, high-availability service. However, we reserve the right to modify, update, suspend, or discontinue any aspect of the service with or without notice for scheduled maintenance, security upgrades, or feature enhancements. We will not be liable for any temporary downtime or inability to access the platform.
            </p>

            <h2>8. Disclaimer of Warranties</h2>
            <p>
                THE PLATFORM AND ALL RELATED SERVICES ARE PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS, WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR UNINTERRUPTED AVAILABILITY.
            </p>

            <h2>9. Limitation of Liability</h2>
            <p>
                TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, KI SOFTWARE AND KI BUSINESS SOLUTIONS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS, DATA, GOODWILL, OR BUSINESS INTERRUPTION, ARISING OUT OF OR IN CONNECTION WITH YOUR USE OF OR INABILITY TO USE THE PLATFORM.
            </p>

            <h2>10. Termination</h2>
            <p>
                We reserve the right to suspend or terminate your account and access to the platform immediately, without prior notice, if you breach any provision of these Terms or engage in fraudulent or abusive activities. Upon termination, your right to use the platform will immediately cease.
            </p>

            <h2>11. Governing Law &amp; Jurisdiction</h2>
            <p>
                These Terms shall be governed by and construed in accordance with the laws of the Republic of Turkey, without regard to its conflict of law principles. Any dispute arising under or in connection with these Terms shall be subject to the exclusive jurisdiction of the competent courts and enforcement offices in Istanbul, Turkey.
            </p>

            <h2>12. Contact Information</h2>
            <p>For questions or notices regarding these Terms, please contact:</p>
            <p>
                <strong>Ki Software / Ki Business Solutions</strong><br>
                Website: <a href="https://kibusiness.co" target="_blank" rel="noopener">https://kibusiness.co</a><br>
                Email: <a href="mailto:support@kibusiness.co">support@kibusiness.co</a><br>
                Legal Affairs: <a href="mailto:privacy@kibusiness.co">privacy@kibusiness.co</a>
            </p>
        </article>

        <!-- ================= TURKISH SECTION ================= -->
        <article class="policy-card" id="termsTurkish" style="display:none;">
            <h2>1. Şartların Kabulü</h2>
            <p>
                Bu Kullanım Şartları ("Şartlar"), <strong>Ki Business Solutions</strong> bünyesindeki <strong>Ki Software</strong> ("biz", "tarafımız") tarafından sunulan ve işletilen <strong>BooKi</strong> online randevu ve salon yönetim platformuna (<strong>https://booki.kibusiness.co</strong> ve <strong>https://bookiapp.kibusiness.co</strong>) erişiminizi ve kullanımınızı düzenleyen yasal bir sözleşmedir.
            </p>
            <p>
                BooKi üzerinde bir hesap oluşturarak veya platformu kullanarak bu Şartları ve <a href="<?= base_url('privacy') ?>">Gizlilik Politikamızı</a> okuduğunuzu, anladığınızı ve bunlarla bağlı kalmayı kabul ettiğinizi beyan etmiş olursunuz.
            </p>

            <h2>2. Hizmetin Tanımı ve Kapsamı</h2>
            <p>
                BooKi; kuaför, güzellik salonu, spa, klinik ve benzeri randevu esaslı hizmet işletmelerine yönelik bulut tabanlı bir rezervasyon ve yönetim sistemidir. Sistem; online randevu oluşturma, personel komisyon hesaplama, takvim yönetimi, müşteri kaydı, adisyon/kasa takibi ve Google Takvim gibi üçüncü taraf sistemlerle entegrasyon imkanı sunar.
            </p>

            <h2>3. Hesap Oluşturma ve Güvenlik</h2>
            <ul>
                <li>Kullanıcılar kayıt esnasında doğru, eksiksiz ve güncel bilgiler sağlamakla yükümlüdür.</li>
                <li>Kullanıcı adı, şifre ve hesap erişim bilgilerinizin gizliliğini korumak tamamen sizin sorumluluğunuzdadır.</li>
                <li>Hesabınızda gerçekleşen yetkisiz bir erişimi fark etmeniz durumunda derhal tarafımıza bilgi vermeniz gerekmektedir.</li>
            </ul>

            <h2>4. Üçüncü Taraf Entegrasyonları ve Google API Hizmetleri</h2>
            <p>
                BooKi, randevu takvimlerinizi otomatik senkronize edebilmeniz için Google API'leri ile entegrasyon sunar.
            </p>
            <div class="highlight-box">
                <h4>Google API Hizmetleri Uyumluluğu</h4>
                <p>
                    Google Hesabınızı BooKi'ye bağladığınızda:
                </p>
                <ul style="margin-left: 1.25rem; margin-top: 0.5rem;">
                    <li>BooKi'ye yalnızca randevu senkronizasyonu ve müsaitlik tespiti amacıyla Google Takviminize erişim izni vermiş olursunuz.</li>
                    <li>BooKi'nin Google API'lerinden aldığı bilgileri kullanımı ve aktarımı, Sınırlı Kullanım gereksinimleri dahil olmak üzere <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener">Google API Services User Data Policy</a> şartlarına tabidir.</li>
                    <li>Google entegrasyonunu BooKi yönetim panelinizden veya Google Hesap İzinleri sayfasından dilediğiniz an tek tıkla iptal edebilirsiniz.</li>
                </ul>
            </div>

            <h2>5. Kabul Edilebilir Kullanım Kuralları</h2>
            <p>Platformu kullanırken aşağıdaki eylemleri gerçekleştirmemeyi kabul etmektesiniz:</p>
            <ul>
                <li>Yürürlükteki ulusal veya uluslararası mevzuata ve kişisel verilerin korunması kanunlarına (KVKK / GDPR) aykırı davranmak.</li>
                <li>İstenmeyen ticari ileti (spam) göndermek veya yetkisiz pazarlama faaliyetleri yürütmek.</li>
                <li>Sistemin kaynak kodlarına erişmeye çalışmak, tersine mühendislik uygulamak veya güvenlik açıklarını kötüye kullanmak.</li>
                <li>Platforma virüs, truva atı veya zararlı kod yüklemek.</li>
            </ul>

            <h2>6. Fikri Mülkiyet Hakları</h2>
            <p>
                BooKi platformunun yazılım mimarisi, kaynak kodları, veri tabanı yapıları, tasarımları, logoları ve tescilli markaları münhasıran <strong>Ki Software / Ki Business Solutions</strong>'a aittir ve fikri mülkiyet hukuku koruması altındadır.
            </p>

            <h2>7. Hizmet Sürekliliği ve Değişiklikler</h2>
            <p>
                Hizmetlerimizin kesintisiz ve yüksek performansla çalışması için gerekli azami çaba gösterilmektedir. Bununla birlikte, planlı bakım, altyapı güncellemeleri veya teknik zorunluluklar sebebiyle sistem üzerinde değişiklik yapma veya geçici olarak durdurma hakkımız saklıdır.
            </p>

            <h2>8. Sorumluluğun Sınırlandırılması</h2>
            <p>
                Yasal mevzuatın izin verdiği azami ölçüde, Ki Software doğrudan veya dolaylı olarak doğabilecek kâr kaybı, veri kaybı veya iş kesintisi gibi zararlardan sorumlu tutulamaz. Hizmetler "olduğu gibi" (as-is) esasıyla sunulmaktadır.
            </p>

            <h2>9. Fesih</h2>
            <p>
                Bu şartlara aykırı hareket edilmesi veya kötü niyetli kullanım tespit edilmesi durumunda, Ki Software ilgili hesabın erişimini tek taraflı olarak derhal durdurma veya feshetme hakkına sahiptir.
            </p>

            <h2>10. Yetkili Mahkeme ve Uygulanacak Hukuk</h2>
            <p>
                Bu Kullanım Şartları Türkiye Cumhuriyeti kanunlarına tabidir. İşbu sözleşmeden doğabilecek her türlü uyuşmazlıkta İstanbul Mahkemeleri ve İcra Daireleri münhasır yetkilidir.
            </p>

            <h2>11. İletişim Bilgileri</h2>
            <p>Şartlara ilişkin her türlü soru ve bildirimleriniz için:</p>
            <p>
                <strong>Ki Software / Ki Business Solutions</strong><br>
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
    const enBlock = document.getElementById('termsEnglish');
    const trBlock = document.getElementById('termsTurkish');
    const btnEn = document.getElementById('btnEn');
    const btnTr = document.getElementById('btnTr');
    const heroTitle = document.getElementById('heroTitle');
    const heroSubtitle = document.getElementById('heroSubtitle');

    if (lang === 'tr') {
        enBlock.style.display = 'none';
        trBlock.style.display = 'block';
        btnEn.classList.remove('active');
        btnTr.classList.add('active');
        heroTitle.innerText = 'BooKi Kullanım Şartları';
        heroSubtitle.innerText = 'BooKi platformunun ve hizmetlerimizin kullanımına ilişkin şart ve kurallar.';
        document.documentElement.lang = 'tr';
    } else {
        enBlock.style.display = 'block';
        trBlock.style.display = 'none';
        btnTr.classList.remove('active');
        btnEn.classList.add('active');
        heroTitle.innerText = 'BooKi Terms of Service';
        heroSubtitle.innerText = 'Please review the terms and conditions governing the use of the BooKi platform and our services.';
        document.documentElement.lang = 'en';
    }
}
</script>

</body>
</html>

