// HostPanel Service Worker v1.0
const CACHE_NAME     = 'hostpanel-v1';
const OFFLINE_URL    = '/pwa/offline';
const STATIC_ASSETS  = [
    '/',
    '/offline',
    '/manifest.json',
];

// ─── Install ───────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(() => {});
        })
    );
    self.skipWaiting();
});

// ─── Activate ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// ─── Fetch ────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorar extensões de recursos não-HTTP e Chrome extensions
    if (!url.protocol.startsWith('http')) return;

    // Requisições de API: Network First, sem fallback offline
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request, false));
        return;
    }

    // Requisições POST/PUT/DELETE: sempre network
    if (request.method !== 'GET') return;

    // Arquivos estáticos (CSS, JS, imagens): Cache First
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Páginas HTML: Network First com fallback offline
    event.respondWith(networkFirst(request, true));
});

// ─── Strategies ───────────────────────────────────────────────────────────────
async function networkFirst(request, offlineFallback) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        if (offlineFallback) {
            const offline = await caches.match(OFFLINE_URL);
            return offline || new Response('<h1>Offline</h1>', {
                headers: { 'Content-Type': 'text/html' },
                status: 503,
            });
        }

        return new Response(JSON.stringify({ error: 'Offline', message: 'Sem conexão com a internet.' }), {
            headers: { 'Content-Type': 'application/json' },
            status: 503,
        });
    }
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 404 });
    }
}

function isStaticAsset(pathname) {
    return /\.(css|js|woff2?|ttf|eot|png|jpg|jpeg|gif|svg|ico|webp)$/.test(pathname)
        || pathname.startsWith('/build/')
        || pathname.startsWith('/images/');
}

// ─── Push Notifications ───────────────────────────────────────────────────────
self.addEventListener('push', event => {
    if (!event.data) return;

    let data = {};
    try { data = event.data.json(); } catch { data = { title: 'HostPanel', body: event.data.text() }; }

    event.waitUntil(
        self.registration.showNotification(data.title ?? 'HostPanel', {
            body:  data.body  ?? '',
            icon:  data.icon  ?? '/images/icons/icon-192x192.png',
            badge: data.badge ?? '/images/icons/icon-72x72.png',
            data:  { url: data.url ?? '/cliente/dashboard' },
            actions: data.actions ?? [],
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const targetUrl = event.notification.data?.url ?? '/cliente/dashboard';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const client of list) {
                if (client.url === targetUrl && 'focus' in client) return client.focus();
            }
            return clients.openWindow(targetUrl);
        })
    );
});

// ─── Background Sync ──────────────────────────────────────────────────────────
self.addEventListener('sync', event => {
    if (event.tag === 'sync-offline-actions') {
        event.waitUntil(syncOfflineActions());
    }
});

async function syncOfflineActions() {
    // Implementação futura: sincronizar ações realizadas offline
    console.log('[SW] Background sync executado');
}
