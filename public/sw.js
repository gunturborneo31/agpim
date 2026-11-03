const CACHE_NAME = 'agpim-shell-v2';

// Hanya file statis yang disimpan di cache. Permintaan data (JSON/AJAX, mis. pengecekan
// usulan baru, file dokumen) selalu langsung ke server supaya tidak basi dan cache tidak membengkak.
const isStaticAsset = (url) => url.origin === self.location.origin
    && (url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icon-')
        || url.pathname === '/favicon.ico'
        || url.pathname === '/manifest.json');
const OFFLINE_URLS = [
    '/',
    '/manifest.json',
    '/favicon.ico',
    '/icon-192x192.png',
    '/icon-512x512.png',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(OFFLINE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match('/') )
        );
        return;
    }

    if (!isStaticAsset(requestUrl)) {
        return; // biarkan browser mengambil langsung dari jaringan
    }

    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request)
                .then(response => {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match('/'));
        })
    );
});
