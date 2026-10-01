/*
 * Mehar Filling Station ERP — Service Worker (app shell only)
 *
 * Policy: sirf static shell cache hota hai (manifest, icons, offline page,
 * Vite build assets). Pages aur API hamesha network se aate hain — financial
 * data kabhi cache nahi hota. Offline par navigations ko offline.html milta hai.
 */
'use strict';

var CACHE_NAME = 'mehar-shell-v1';

var SHELL_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
    '/icons/apple-touch-icon.png'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function (cache) { return cache.addAll(SHELL_ASSETS); })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(keys.map(function (key) {
                    return key === CACHE_NAME ? Promise.resolve() : caches.delete(key);
                }));
            })
            .then(function () { return self.clients.claim(); })
    );
});

function isStaticShell(url) {
    return url.pathname.indexOf('/build/') === 0
        || url.pathname.indexOf('/icons/') === 0
        || url.pathname === '/manifest.webmanifest'
        || url.pathname === '/favicon.ico';
}

self.addEventListener('fetch', function (event) {
    var request = event.request;

    if (request.method !== 'GET') {
        return; // POST/PUT waghera hamesha seedha network
    }

    var url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return; // cross-origin: browser default
    }

    // Static shell: cache-first, network se refresh bhi kar do.
    if (isStaticShell(url)) {
        event.respondWith(
            caches.match(request).then(function (hit) {
                var network = fetch(request).then(function (response) {
                    if (response && response.ok) {
                        var copy = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) { cache.put(request, copy); });
                    }
                    return response;
                }).catch(function () { return hit; });
                return hit || network;
            })
        );
        return;
    }

    // Page navigations: network-first, offline par offline page.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(function () {
                return caches.match('/offline.html');
            })
        );
        return;
    }

    // Baqi sab (API/JSON waghera): hamesha network — respondWith nahi,
    // taake fresh financial data hi aaye, cache kabhi darmiyan na aaye.
});
