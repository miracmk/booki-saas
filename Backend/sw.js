const CACHE_NAME = 'booki-v2';
const STATIC_ASSETS = [
    '/manifest.json',
    '/assets/css/general.min.css',
    '/assets/css/backend.min.css',
    '/assets/css/ki-command-center.min.css',
    '/assets/js/app.min.js',
    '/assets/img/logo.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => console.warn('SW Precache Warning:', err));
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    // Only intercept GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Ignore cross-origin API / extension requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Navigation / HTML requests: Network-First with offline fallback
    const isNavigation = event.request.mode === 'navigate' ||
        (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html'));

    if (isNavigation) {
        event.respondWith(
            fetch(event.request)
                .then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
                    }
                    return networkResponse;
                })
                .catch(() => {
                    return caches.match(event.request).then(cachedResponse => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        return new Response(
                            `<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Çevrimdışı | BooKi</title>
    <style>
        :root {
            --brand-primary: #35A768;
            --brand-primary-hover: #2d8f58;
            --surface: #ffffff;
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .offline-card { background: var(--surface); border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 44px 32px; text-align: center; max-width: 440px; width: 100%; border: 1px solid var(--border); }
        .icon-circle { width: 80px; height: 80px; background: rgba(53, 167, 104, 0.12); color: var(--brand-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 36px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 12px; color: var(--text); letter-spacing: -0.02em; }
        p { font-size: 14.5px; color: var(--muted); line-height: 1.6; margin-bottom: 28px; }
        .retry-btn { background: var(--brand-primary); color: #ffffff; border: none; border-radius: 10px; padding: 12px 24px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; width: 100%; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(53, 167, 104, 0.25); }
        .retry-btn:hover { background: var(--brand-primary-hover); transform: translateY(-1px); }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--muted); margin-top: 18px; }
        .status-dot { width: 8px; height: 8px; background: #ef4444; border-radius: 50%; }
    </style>
</head>
<body>
    <div class="offline-card" role="alert" aria-live="assertive">
        <div class="icon-circle" aria-hidden="true">⚡</div>
        <h1>İnternet Bağlantısı Yok</h1>
        <p>Şu anda çevrimdışı görünüyorsunuz. İnternet bağlantınız sağlandığında sistem otomatik olarak yeniden bağlanacaktır.</p>
        <button class="retry-btn" onclick="window.location.reload()">Tekrar Dene</button>
        <div class="status-badge"><span class="status-dot"></span> Bağlantı bekleniyor...</div>
    </div>
    <script>
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>`,
                            {
                                headers: { 'Content-Type': 'text/html; charset=utf-8' },
                            }
                        );
                    });
                })
        );
        return;
    }

    // Static assets (CSS, JS, Images, Fonts): Cache-First with Network fallback
    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(event.request).then(networkResponse => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
                }
                return networkResponse;
            });
        })
    );
});
