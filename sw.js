/**
 * Akti — Service Worker (PWA)
 * Gestão em Produção
 *
 * Estratégia: Network First com cache fallback para assets estáticos.
 * Cobre o app principal (admin) — o portal tem seu próprio SW (portal-sw.js).
 */

const CACHE_NAME = 'akti-app-v1';
const STATIC_ASSETS = [
    'assets/css/design-system.css',
    'assets/css/theme.css',
    'assets/css/style.css',
    'assets/js/script.js',
    'assets/logos/akti-icon-dark.svg',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
];

// ── Install: cachear assets estáticos ──
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS).catch(function (err) {
                console.warn('SW: Falha ao cachear alguns assets:', err);
            });
        })
    );
    self.skipWaiting();
});

// ── Activate: limpar caches antigos ──
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames
                    .filter(function (name) {
                        return name !== CACHE_NAME;
                    })
                    .map(function (name) {
                        return caches.delete(name);
                    })
            );
        })
    );
    self.clients.claim();
});

// ── Fetch: assets estáticos → cache first; páginas → network first ──
self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    var url = new URL(event.request.url);

    // Assets estáticos: stale-while-revalidate
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(function (cache) {
                return cache.match(event.request).then(function (cached) {
                    var fetchPromise = fetch(event.request).then(function (response) {
                        if (response && response.status === 200) {
                            cache.put(event.request, response.clone());
                        }
                        return response;
                    }).catch(function () {
                        return cached;
                    });
                    return cached || fetchPromise;
                });
            })
        );
        return;
    }
});

// ── Push Notifications ──
self.addEventListener('push', function (event) {
    var data = {
        title: 'Akti',
        body: 'Nova notificação.',
        icon: 'assets/logos/akti-icon-dark.svg',
        badge: 'assets/logos/akti-icon-dark.svg',
        url: '?page=notifications',
        tag: 'akti-notification'
    };

    if (event.data) {
        try {
            var payload = event.data.json();
            data.title = payload.title || data.title;
            data.body = payload.body || data.body;
            data.url = payload.url || data.url;
            data.tag = payload.tag || data.tag;
        } catch (e) {
            data.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            badge: data.badge,
            tag: data.tag,
            data: { url: data.url }
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = event.notification.data && event.notification.data.url
        ? event.notification.data.url
        : '/';
    event.waitUntil(
        clients.openWindow(url)
    );
});

/**
 * Verifica se a URL é um asset estático cacheável
 */
function isStaticAsset(url) {
    var path = url.pathname;
    return /\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico|webp)$/i.test(path);
}
