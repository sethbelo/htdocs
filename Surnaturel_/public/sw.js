/* Surnaturel Print — service worker : application installable, ressources en cache, page hors ligne. */
const VERSION = 'surnaturel-v2';
const SHELL = ['/offline.html', '/brand/logo.svg', '/brand/mark.svg', '/brand/app-icon-192.png', '/favicon.svg', '/images/backgrounds/ink-light.webp', '/images/backgrounds/ink-dark.webp'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Pages : toujours le réseau (données à jour), page hors ligne en secours.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    // Ressources statiques versionnées : cache d'abord, mise à jour en arrière-plan.
    if (/^\/(build|brand|images|fonts)\//.test(url.pathname) || /\.(?:svg|png|webp|jpg|woff2?)$/.test(url.pathname)) {
        event.respondWith(caches.open(VERSION).then(async (cache) => {
            const cached = await cache.match(request);
            const network = fetch(request).then((response) => {
                if (response.ok) {
                    cache.put(request, response.clone());
                }

                return response;
            }).catch(() => cached);

            return cached || network;
        }));
    }
});
