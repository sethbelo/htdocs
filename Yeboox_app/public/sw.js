/**
 * Yeboox POS — Service Worker
 * Stratégie: Cache-First pour assets, Network-First pour API/pages
 */

const CACHE_NAME = 'yeboox-pos-v1';
const OFFLINE_URL = '/offline';

// Assets à pré-cacher au moment de l'installation
const PRECACHE_ASSETS = [
    '/',
    '/offline',
    '/manifest.json',
    '/build/assets/app.css',
    '/build/assets/app.js',
];

// ── Installation ────────────────────────────────────────
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Yeboox POS Service Worker v1');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ── Activation — nettoyage des anciens caches ───────────
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// ── Fetch — Stratégies de cache ─────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-GET
    if (request.method !== 'GET') return;

    // Ignorer les requêtes Livewire (temps réel)
    if (url.pathname.includes('/livewire/')) return;

    // Ignorer les requêtes d'API sync (doivent toujours aller au réseau)
    if (url.pathname.startsWith('/api/')) return;

    // ── Stratégie Cache-First pour assets statiques ──
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // ── Stratégie Network-First pour pages HTML ──
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirst(request));
        return;
    }

    // ── Stratégie StaleWhileRevalidate pour le reste ──
    event.respondWith(staleWhileRevalidate(request));
});

// ── Cache-First : assets statiques ──────────────────────
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
    } catch (error) {
        return new Response('Asset offline', { status: 503 });
    }
}

// ── Network-First : pages dynamiques ────────────────────
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;

        // Page offline de secours
        const offlinePage = await caches.match(OFFLINE_URL);
        if (offlinePage) return offlinePage;

        return new Response(offlineHTML(), {
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
        });
    }
}

// ── Stale-While-Revalidate ──────────────────────────────
async function staleWhileRevalidate(request) {
    const cached = await caches.match(request);

    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
        }
        return response;
    }).catch(() => cached);

    return cached || fetchPromise;
}

// ── Détection assets statiques ──────────────────────────
function isStaticAsset(url) {
    const exts = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.webp'];
    return exts.some(ext => url.pathname.endsWith(ext)) || url.pathname.startsWith('/build/');
}

// ── Page offline de secours (inline) ────────────────────
function offlineHTML() {
    return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeboox POS — Hors Connexion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 2rem;
        }
        .container { max-width: 420px; }
        .icon { font-size: 4rem; margin-bottom: 1.5rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: #f8fafc; }
        p { color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-block; padding: 0.75rem 2rem;
            background: #10b981; color: white; border: none;
            border-radius: 0.75rem; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: all 0.2s;
        }
        .btn:hover { background: #059669; transform: translateY(-1px); }
        .status { margin-top: 2rem; font-size: 0.8rem; color: #64748b; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📡</div>
        <h1>Mode Hors Connexion</h1>
        <p>Vous n'êtes pas connecté à Internet. Les commandes en cours ont été sauvegardées localement et seront synchronisées automatiquement dès le retour de la connexion.</p>
        <button class="btn" onclick="window.location.reload()">🔄 Réessayer</button>
        <div class="status pulse">⏳ En attente de connexion...</div>
    </div>
    <script>
        // Auto-reload when connection returns
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>`;
}

// ── Background Sync (si supporté) ───────────────────────
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOfflineOrders());
    }
});

async function syncOfflineOrders() {
    // Déclenché quand le réseau revient — sera connecté à IndexedDB
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
        client.postMessage({ type: 'SYNC_TRIGGER', message: 'Network restored — syncing orders' });
    });
}

// ── Push Notifications (futur) ──────────────────────────
self.addEventListener('push', (event) => {
    if (!event.data) return;
    const data = event.data.json();
    event.waitUntil(
        self.registration.showNotification(data.title || 'Yeboox POS', {
            body: data.body || '',
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-72x72.png',
            tag: data.tag || 'yeboox-notification',
        })
    );
});
