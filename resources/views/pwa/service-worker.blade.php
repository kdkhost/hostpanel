const CACHE_NAME = 'hostpanel-v1.0.0';
const STATIC_ASSETS = [
    '/',
    '/cliente/dashboard',
    '/cliente/servicos',
    '/cliente/faturas',
    '/offline',
];

// Install: cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
    );
});

// Activate: clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// Fetch: network-first for API, cache-first for assets
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET and cross-origin
    if (request.method !== 'GET' || !url.origin.includes(self.location.origin)) return;

    // API: network-only
    if (url.pathname.startsWith('/api/')) return;

    // Admin: network-only
    if (url.pathname.startsWith('/admin/')) return;

    // Static assets: cache-first
    if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/)) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(res => {
                if (res.ok) {
                    const clone = res.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                }
                return res;
            }))
        );
        return;
    }

    // HTML: network-first, fallback to cache, then offline page
    event.respondWith(
        fetch(request).then(res => {
            if (res.ok) {
                const clone = res.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
            }
            return res;
        }).catch(() =>
            caches.match(request).then(cached =>
                cached || caches.match('/offline')
            )
        )
    );
});

// Push Notifications
self.addEventListener('push', event => {
    if (!event.data) return;
    const data = event.data.json();
    event.waitUntil(
        self.registration.showNotification(data.title || '{{ config("app.name") }}', {
            body: data.body || '',
            icon: '/images/icons/icon-192x192.png',
            badge: '/images/icons/icon-72x72.png',
            data: { url: data.url || '/cliente/dashboard' },
            actions: [{ action: 'open', title: 'Abrir' }],
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/cliente/dashboard';
    event.waitUntil(clients.openWindow(url));
});
