/**
 * Portal do Cliente — Service Worker (PWA)
 * Akti - Gestão em Produção
 *
 * Estratégia: Network First com fallback para cache.
 * Cache estático para assets (CSS, JS, fontes, ícones).
 */

const CACHE_NAME = 'portal-v1';
const STATIC_ASSETS = [
    'assets/css/portal.css',
    'assets/js/portal.js',
    'assets/logos/akti-icon-dark.svg',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
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

// ── Fetch: Network first, fallback to cache ──
self.addEventListener('fetch', function (event) {
    const url = new URL(event.request.url);

    // Não cachear requisições POST
    if (event.request.method !== 'GET') {
        return;
    }

    // Para assets estáticos: cache first
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(event.request).then(function (cached) {
                if (cached) {
                    return cached;
                }
                return fetch(event.request).then(function (response) {
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Para páginas HTML: network first
    if (event.request.headers.get('Accept') && event.request.headers.get('Accept').includes('text/html')) {
        event.respondWith(
            fetch(event.request)
                .then(function (response) {
                    return response;
                })
                .catch(function () {
                    return caches.match(event.request);
                })
        );
        return;
    }
});

/**
 * Verifica se a URL é um asset estático.
 */
function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.svg', '.png', '.jpg', '.ico', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(function (ext) {
        return url.pathname.endsWith(ext);
    });
}
