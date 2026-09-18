const CACHE_NAME = 'booki-v1';
const STATIC_ASSETS = [
    '/',
    '/calendar',
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
            return cache.addAll(STATIC_ASSETS).catch(err => console.log('SW Cache Warning:', err));
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

    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request).catch(() => {
                if (event.request.headers.get('accept')?.includes('text/html')) {
                    return new Response(
                        `<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Çevrimdışı | BooKi</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .offline-card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 40px 30px; text-align: center; max-width: 440px; width: 100%; border: 1px solid #e2e8f0; }
        .icon-circle { width: 72px; height: 72px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 32px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 12px; color: #0f172a; }
        p { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .retry-btn { background: #0284c7; color: #ffffff; border: none; border-radius: 8px; padding: 12px 24px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s ease; width: 100%; }
        .retry-btn:hover { background: #0369a1; }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="icon-circle">📡</div>
        <h1>İnternet Bağlantısı Yok</h1>
        <p>Şu anda çevrimdışı görünüyorsunuz. Lütfen internet bağlantınızı kontrol edip tekrar deneyiniz.</p>
        <button class="retry-btn" onclick="window.location.reload()">Tekrar Dene</button>
    </div>
</body>
</html>`,
                        {
                            headers: { 'Content-Type': 'text/html; charset=utf-8' },
                        }
                    );
                }
            });
        })
    );
});
