<?php
/**
 * @var string $google_analytics_code
 * @var string|null $meta_pixel_id Ki Reservation (2026-09-12) - Meta Pixel, optional, tenant setting
 *   `meta_pixel_id` (mirrors `google_analytics_code`'s pattern). Standard events (PageView here,
 *   Schedule fired separately on booking_confirmation.php) let Meta Ads attribute bookings the same
 *   way the tenant's main marketing site already does (see salonflora.tr's own Pixel setup).
 */
?>

<?php if (substr($google_analytics_code ?? '', 0, 2) === 'UA'): ?>
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
        ga("create", "<?= e($google_analytics_code) ?>", "auto");
        ga("send", "pageview");
    </script>
<?php endif; ?>

<?php if (substr($google_analytics_code ?? '', 0, 2) === 'G-'): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($google_analytics_code) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag("js", new Date());
        gtag("config", "<?= e($google_analytics_code) ?>");
    </script>
<?php endif; ?>

<?php if (!empty($meta_pixel_id)): ?>
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
        fbq("init", "<?= e($meta_pixel_id) ?>");
        fbq("track", "PageView");
    </script>
<?php endif; ?>

