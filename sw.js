const CACHE_NAME = 'booki-v1';
const STATIC_ASSETS = [
    '/',
    '/calendar',
    '/manifest.json',
    '/assets/css/app.min.css',
    '/assets/js/app.min.js',
    '/assets/img/logo.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => console.log('SW Cache Error', err));
        })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request).catch(() => {
                // Return offline fallback or something
                return new Response('<h1>Offline</h1><p>Lütfen internet bağlantınızı kontrol ediniz.</p>', {
                    headers: {'Content-Type': 'text/html'}
                });
            });
        })
    );
});
