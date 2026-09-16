<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(vars('page_title')) ?></title>
    <meta name="description" content="BooKi; randevu alımı, takvim, müşteri yönetimi, ödeme takibi ve WhatsApp entegrasyonunu tek panelde toplayan online randevu sistemidir.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= base_url('/') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e(vars('page_title')) ?>">
    <meta property="og:description" content="İşletmeniz için online randevu, müşteri ve takvim yönetimi tek panelde. WhatsApp entegrasyonu ile.">
    <meta property="og:url" content="<?= base_url('/') ?>">
    <link rel="icon" type="image/png" href="<?= asset_url('img/logo-16x16.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&family=DM+Serif+Display&display=swap" rel="stylesheet">
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

        /* Marketplace strip */
        .marketplace { background: #fff; border-top: 1px solid #e6ebee; border-bottom: 1px solid #e6ebee; }
        .marketplace-head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        .tenant-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
        .tenant-card { background: var(--bg); border: 1px solid #e6ebee; border-radius: var(--radius); padding: 1.4rem; }
        .tenant-card h3 { font-size: 1.05rem; color: var(--navy); }
        .tenant-card .tenant-card__meta { color: var(--muted); font-size: .86rem; margin: .3rem 0 .8rem; }
        .tenant-card .tenant-card__stars { color: #f2b01e; font-size: .9rem; }
        .tenant-card .btn { margin-top: .5rem; }

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

        /* ---------- Responsive ---------- */
        @media (max-width: 880px) {
            .hero .container { grid-template-columns: 1fr; padding-top: 3rem; }
            .features-grid, .steps, .tenant-cards, .stats .container { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .float-badge--avg { left: -0.3rem; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container">
        <a class="brand" href="<?= base_url('/') ?>" aria-label="BooKi ana sayfa">
            <span class="brand__logo">BOO·KI</span>
        </a>
        <nav class="nav-links" aria-label="Ana gezinme">
            <a href="#ozellikler">Özellikler</a>
            <a href="#nasil-calisir">Nasıl Çalışır</a>
            <a href="#isletmeler">İşletmeler</a>
            <a class="btn btn--outline btn--sm" href="<?= base_url('marketplace') ?>">Keşfet</a>
            <a class="btn btn--primary btn--sm" href="<?= e(vars('portal_url')) ?>">Giriş Yap</a>
        </nav>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <div>
                <span class="hero__kicker">✦ İşletmeler için online randevu sistemi</span>
                <h1>Randevularınızı, takviminizi ve müşterilerinizi <em>tek panelde</em> yönetin.</h1>
                <p class="lead">BooKi; online randevu alımı, takvim yönetimi, WhatsApp hatırlatmaları, üyelikler, paketler ve ödeme takibini tek yerden sunar — siz işinize odaklanın.</p>
                <div class="hero__actions">
<a class="btn btn--light" href="<?= e(vars('portal_url')) ?>">Hemen Başlayın</a>
                    <a class="btn btn--ghost" href="#ozellikler">Özellikleri İncele</a>
                </div>
                <p class="hero__proof">Aktif <strong>işletmeler</strong> tarafından kullanılıyor · Mobil uyumlu · Kurulum gerektirmez</p>
            </div>
            <div class="hero__visual">
                <div class="browser-card">
                    <div class="browser-card__bar"><i></i><i></i><i></i></div>
                    <div class="browser-card__body">
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Yarın · 14:00</div>
                                <div class="mini-row__sub">Ayşe K. — Saç Bakımı</div>
                            </div>
                            <span class="mini-row__status">Onaylı</span>
                        </div>
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Yarın · 15:30</div>
                                <div class="mini-row__sub">Mehmet D. — Manikür</div>
                            </div>
                            <span class="mini-row__status">Onaylı</span>
                        </div>
                        <div class="mini-row">
                            <div>
                                <div class="mini-row__title">Cumartesi · 11:00</div>
                                <div class="mini-row__sub">Zeynep T. — Cilt Bakımı</div>
                            </div>
                            <span class="mini-chip">Bekleme Listesi</span>
                        </div>
                    </div>
                </div>
                <div class="float-badge float-badge--wa">WhatsApp hatırlatması gönderildi ✓</div>
                <div class="float-badge float-badge--avg">
                    <span class="float-badge__stars">★★★★★</span>
                    <span>4.9 ortalaması</span>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="stats" aria-hidden="true">
        <div class="container">
            <div><div class="stat__num">%40</div><div class="stat__label">daha az no-show</div></div>
            <div><div class="stat__num">7/24</div><div class="stat__label">online randevu alımı</div></div>
            <div><div class="stat__num">+15</div><div class="stat__label">entegrasyon</div></div>
            <div><div class="stat__num">1 dk</div><div class="stat__label">kurulum</div></div>
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

    <!-- MARKETPLACE -->
    <section class="marketplace" id="isletmeler">
        <div class="container">
            <div class="marketplace-head">
                <div class="section-head" style="margin-bottom:0;">
                    <h2>Marketplace'teki işletmeler</h2>
                    <p>BooKi marketplace'inde aktif olarak hizmet veren işletmelere göz atın.</p>
                </div>
                <a class="btn btn--outline" href="<?= base_url('marketplace') ?>">Tümünü Gör</a>
            </div>

            <?php if (empty(vars('featured_tenants'))): ?>
                <p style="color:var(--muted);">Henüz liste dışı bir işletme yok. İlk işletme olun!</p>
            <?php else: ?>
                <div class="tenant-cards">
                    <?php foreach (vars('featured_tenants') as $tenant): ?>
                        <?php
                            $site = $tenant['custom_domain'] ?? ($tenant['subdomain'] . '-' . vars('app_domain'));
                            $review_count = (int) ($tenant['review_count'] ?? 0);
                            $avg_rating = round((float) ($tenant['avg_rating'] ?? 0), 1);
                        ?>
                        <div class="tenant-card">
                            <h3><?= e($tenant['company_name'] ?? $tenant['subdomain']) ?></h3>
                            <div class="tenant-card__meta">
                                <?= e($tenant['category'] ?? 'Hizmet') ?>
                                <?= !empty($tenant['city']) ? '· ' . e($tenant['city']) : '' ?>
                            </div>
                            <div class="tenant-card__stars">
                                <?= str_repeat('★', max(0, (int) round($avg_rating))) ?>
                                <?= $review_count > 0 ? ('(' . $review_count . ')') : 'Henüz yorum yok' ?>
                            </div>
                            <a class="btn btn--primary btn--sm" href="https://<?= e($site) ?>/">Randevu Al</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>İşletmenizi dijitalleştirmeye hazır mısınız?</h2>
            <p>BooKi'yi bugün kullanmaya başlayın. Kurulum gerektirmez, dakikalar içinde randevu almaya başlayın.</p>
            <a class="btn btn--light" href="<?= base_url('portal') ?>">Hemen Başlayın</a>
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
                <li><a href="#ozellikler">Özellikler</a></li>
                <li><a href="#nasil-calisir">Nasıl Çalışır</a></li>
                <li><a href="<?= base_url('marketplace') ?>">Marketplace</a></li>
                <li><a href="<?= e(vars('portal_url')) ?>">Giriş Yap</a></li>
            </ul>
        </div>
        <div>
            <h4>Şirket</h4>
            <ul>
                <li><a href="https://kisoftware.com" target="_blank" rel="noopener">Ki Software</a></li>
            </ul>
        </div>
    </div>
    <div class="site-footer__bottom">
        <div class="container">© <?= date('Y') ?> BooKi · Ki Software. Tüm hakları saklıdır.</div>
    </div>
</footer>

</body>
</html>