const CACHE_NAME = 'articlepulsehub-cache-v1';
const MAX_CACHE_ITEMS = 100; // maximum number of cached requests

// Install event
self.addEventListener('install', event => {
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

// Helper: Trim cache to limit size
async function trimCache(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
        await cache.delete(keys[0]);
        trimCache(cacheName, maxItems);
    }
}

// Fetch event: network-first for dynamic pages, cache-first for offline
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then(networkResponse => {
                // Cache the response dynamically
                return caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, networkResponse.clone());
                    trimCache(CACHE_NAME, MAX_CACHE_ITEMS);
                    return networkResponse;
                });
            })
            .catch(() => {
                // Offline: serve from cache
                return caches.match(event.request)
                    .then(cachedResponse => {
                        if (cachedResponse) return cachedResponse;
                        // If it's a navigation request, fallback to index.php
                        if (event.request.mode === 'navigate') {
                            return caches.match('/index.php');
                        }
                    });
            })
    );
});
