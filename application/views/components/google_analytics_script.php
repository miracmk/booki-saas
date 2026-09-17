<?php
/**
 * Google & Meta Analytics, Pixel, GTM, Search Console & Attribution Script Component.
 *
 * Automatically renders all configured marketing tracking tags:
 * - Google Analytics (GA4 / UA)
 * - Google Ads conversion tag
 * - Google Tag Manager (GTM)
 * - Google Search Console verification meta tag
 * - Meta Pixel (Facebook / Instagram)
 * - BooKi Smart Attribution & Heatmap Tracker
 *
 * @var string|null $google_analytics_code
 * @var string|null $meta_pixel_id
 */

$ga_code = $google_analytics_code ?? (setting('google_analytics_id') ?: setting('google_analytics_code'));
$pixel_id = $meta_pixel_id ?? setting('meta_pixel_id');
$google_ads_id = setting('google_ads_id');
$gtm_id = setting('gtm_container_id');
$gsc_token = setting('google_search_console_token');
?>

<?php if (!empty($gsc_token)): ?>
    <!-- Google Search Console verification -->
    <meta name="google-site-verification" content="<?= e($gsc_token) ?>" />
<?php endif; ?>

<?php if (!empty($gtm_id)): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= e($gtm_id) ?>');</script>
    <!-- End Google Tag Manager -->
<?php endif; ?>

<?php if (substr($ga_code ?? '', 0, 2) === 'UA'): ?>
    <script>
        (function (i, s, o, g, r, a, m) {
            i["GoogleAnalyticsObject"] = r;
            i[r] = i[r] || function () {
                (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, "script", "//www.google-analytics.com/analytics.js", "ga");
        ga("create", "<?= e($ga_code) ?>", "auto");
        ga("send", "pageview");
    </script>
<?php endif; ?>

<?php if (substr($ga_code ?? '', 0, 2) === 'G-'): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga_code) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
        gtag("js", new Date());
        gtag("config", "<?= e($ga_code) ?>");
    </script>
<?php endif; ?>

<?php if (!empty($google_ads_id)): ?>
    <?php if (substr($ga_code ?? '', 0, 2) !== 'G-'): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($google_ads_id) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() {
                dataLayer.push(arguments);
            }
            gtag("js", new Date());
        </script>
    <?php endif; ?>
    <script>
        gtag("config", "<?= e($google_ads_id) ?>");
    </script>
<?php endif; ?>

<?php if (!empty($pixel_id)): ?>
    <!-- Meta Pixel Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function () {
                n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n; n.loaded = !0; n.version = "2.0"; n.queue = [];
            t = b.createElement(e); t.async = !0; t.src = v;
            s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
        }(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
        fbq("init", "<?= e($pixel_id) ?>");
        fbq("track", "PageView");
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= e($pixel_id) ?>&ev=PageView&noscript=1" />
    </noscript>
    <!-- End Meta Pixel Code -->
<?php endif; ?>

<!-- BooKi Smart Attribution & Heatmap Tracker -->
<script src="<?= asset_url('assets/js/analytics/booki_tracker' . (config('debug') ? '' : '.min') . '.js') ?>"></script>
