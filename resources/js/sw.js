/* pwa-for-filament service worker — deliberately minimal: Livewire apps break
 * under aggressive caching, so this only precaches the offline fallback (and
 * icons) and serves it when a navigation fails. */
const CONFIG = __PWA_CONFIG__;
const CACHE = CONFIG.cachePrefix + CONFIG.version;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(CONFIG.precache))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key.startsWith(CONFIG.cachePrefix) && key !== CACHE)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) return;
    if (url.pathname.includes('/livewire/')) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(CONFIG.offlineUrl)));

        return;
    }

    if (CONFIG.precache.includes(url.pathname)) {
        event.respondWith(caches.match(request).then((hit) => hit || fetch(request)));
    }
});
