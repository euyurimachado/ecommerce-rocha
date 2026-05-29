const CACHE_VERSION = 'rocha-sports-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const STATIC_ASSETS = [
    '/offline',
    '/images/logo-rocha-sports.webp',
    '/images/pwa-icon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('rocha-sports-') && key !== STATIC_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline')),
        );

        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => cachedResponse ?? fetch(request)),
    );
});
