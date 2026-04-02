/**
 * Loja Service Worker — Cache-first strategy for static assets.
 * Provides offline support for the storefront.
 */

var CACHE_NAME = 'akti-loja-v1';
var STATIC_ASSETS = [
    '/loja/assets/css/theme.css',
    '/loja/assets/js/theme.js',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    var url = new URL(event.request.url);

    // Only cache same-origin GET requests for static assets
    if (event.request.method !== 'GET') return;
    if (url.origin !== self.location.origin) return;

    // Cache-first for CSS, JS, images
    var isStatic = /\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?|ttf|eot|ico)(\?.*)?$/.test(url.pathname);

    if (isStatic) {
        event.respondWith(
            caches.match(event.request).then(function (cached) {
                if (cached) return cached;
                return fetch(event.request).then(function (response) {
                    if (response.ok) {
                        var clone = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                });
            })
        );
    } else {
        // Network-first for HTML/API
        event.respondWith(
            fetch(event.request).catch(function () {
                return caches.match(event.request);
            })
        );
    }
});
