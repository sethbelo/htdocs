import Chart from 'chart.js/auto';
import mapboxgl from 'mapbox-gl';

window.Chart = Chart;
window.mapboxgl = mapboxgl;

/**
 * Graphique Chart.js piloté par Alpine : x-data="chart(config)".
 * La configuration est recalculée quand Livewire met à jour le composant.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Coquille de l'application : barre latérale rétractable (bureau) / tiroir (mobile),
     * état persistant entre les pages via localStorage.
     */
    window.Alpine.store('shell', {
        drawer: false,
        collapsed: (() => {
            try {
                return localStorage.getItem('erp-sidebar') === '1';
            } catch {
                return false;
            }
        })(),
        toggleCollapsed() {
            this.collapsed = ! this.collapsed;
            try {
                localStorage.setItem('erp-sidebar', this.collapsed ? '1' : '0');
            } catch {
                //
            }
        },
    });

    /**
     * Menu latéral : départements dépliés, mémorisés entre les pages ; le département courant est toujours ouvert.
     */
    window.Alpine.store('nav', {
        open: (() => {
            try {
                return JSON.parse(localStorage.getItem('erp-nav-open') || '[]');
            } catch {
                return [];
            }
        })(),
        isOpen(key) {
            return this.open.includes(key);
        },
        toggle(key) {
            this.open = this.isOpen(key) ? this.open.filter((k) => k !== key) : [...this.open, key];
            this.persist();
        },
        ensure(key) {
            if (key && ! this.isOpen(key)) {
                this.open = [...this.open, key];
                this.persist();
            }
        },
        persist() {
            try {
                localStorage.setItem('erp-nav-open', JSON.stringify(this.open));
            } catch {
                //
            }
        },
    });

    /**
     * Synchronisation cloud : état de la connexion, requêtes en cours, dernière synchronisation réussie
     * et contrôle du serveur toutes les 60 s (sans prolonger la session d'inactivité).
     */
    window.Alpine.store('sync', {
        online: navigator.onLine,
        reachable: true,
        pending: 0,
        lastSync: Date.now(),
        now: Date.now(),
        get state() {
            if (! this.online || ! this.reachable) {
                return 'offline';
            }

            return this.pending > 0 ? 'syncing' : 'synced';
        },
        get label() {
            if (this.state === 'offline') {
                return this.online ? 'Serveur injoignable' : 'Hors ligne';
            }
            if (this.state === 'syncing') {
                return 'Synchronisation…';
            }
            const seconds = Math.max(0, Math.round((this.now - this.lastSync) / 1000));

            return seconds < 10 ? 'Synchronisé' : seconds < 60 ? `Synchronisé · ${seconds} s` : `Synchronisé · ${Math.round(seconds / 60)} min`;
        },
        async ping() {
            const url = document.querySelector('meta[name="sync-ping"]')?.content;
            if (! url || ! this.online) {
                return;
            }
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                this.reachable = response.ok;
                if (response.ok) {
                    this.lastSync = Date.now();
                }
            } catch {
                this.reachable = false;
            }
        },
        init() {
            window.addEventListener('online', () => { this.online = true; this.ping(); });
            window.addEventListener('offline', () => { this.online = false; });
            setInterval(() => { this.now = Date.now(); }, 5000);
            setInterval(() => this.ping(), 60000);
        },
    });

    /**
     * Installation comme application de bureau (PWA : Chrome, Edge, Safari « Ajouter au Dock »).
     */
    window.Alpine.store('install', {
        prompt: null,
        installed: window.matchMedia('(display-mode: standalone)').matches || window.matchMedia('(display-mode: window-controls-overlay)').matches,
        get available() {
            return this.prompt !== null && ! this.installed;
        },
        async run() {
            if (! this.prompt) {
                return;
            }
            this.prompt.prompt();
            const { outcome } = await this.prompt.userChoice;
            if (outcome === 'accepted') {
                this.installed = true;
            }
            this.prompt = null;
        },
    });

    /**
     * Palette de commandes (Ctrl/⌘ + K) : recherche instantanée dans les écrans accessibles.
     */
    window.Alpine.data('commandPalette', (items = []) => ({
        open: false,
        query: '',
        active: 0,
        items,
        get filtered() {
            const normalize = (value) => value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            const q = normalize(this.query.trim());
            const list = q ? this.items.filter((i) => normalize(`${i.label} ${i.group}`).includes(q)) : this.items;

            return list.slice(0, 12);
        },
        show() {
            this.open = true;
            this.query = '';
            this.active = 0;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        move(step) {
            this.active = (this.active + step + this.filtered.length) % Math.max(1, this.filtered.length);
        },
        go(item = this.filtered[this.active]) {
            if (item) {
                this.open = false;
                window.Livewire.navigate(item.url);
            }
        },
    }));

    window.Alpine.data('chart', (config) => ({
        instance: null,
        init() {
            const dark = document.documentElement.classList.contains('dark');
            Chart.defaults.color = dark ? '#a9c0ca' : '#525d63';
            Chart.defaults.borderColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(19,74,99,0.07)';
            Chart.defaults.font.family = 'Instrument Sans, ui-sans-serif, system-ui';
            this.instance = new Chart(this.$refs.canvas, config);
        },
        destroy() {
            this.instance?.destroy();
        },
    }));

    /**
     * Carte Mapbox des sièges, boutiques, entrepôts et PDV.
     */
    window.Alpine.data('sitesMap', ({ token, style, center, points }) => ({
        init() {
            if (!token) {
                return;
            }
            mapboxgl.accessToken = token;
            const map = new mapboxgl.Map({ container: this.$refs.map, style, center, zoom: 11 });
            map.addControl(new mapboxgl.NavigationControl(), 'top-right');
            const bounds = new mapboxgl.LngLatBounds();
            points.forEach((point) => {
                const el = document.createElement('div');
                el.className = 'size-3.5 rounded-full ring-2 ring-white shadow';
                el.style.background = point.color;
                new mapboxgl.Marker(el)
                    .setLngLat([point.lng, point.lat])
                    .setPopup(new mapboxgl.Popup({ offset: 12 }).setHTML(`<strong>${point.name}</strong><br><small>${point.kind}</small>`))
                    .addTo(map);
                bounds.extend([point.lng, point.lat]);
            });
            if (points.length > 1) {
                map.fitBounds(bounds, { padding: 48, maxZoom: 13 });
            }
        },
    }));

    /**
     * Carrousel des services : lecture automatique avec progression, pause au survol,
     * navigation clavier, balayage tactile et respect de « réduire les animations ».
     */
    window.Alpine.data('serviceCarousel', ({ count, interval = 6500 }) => ({
        current: 0,
        progress: 0,
        paused: false,
        startX: null,
        elapsed: 0,
        last: null,
        frame: null,
        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        init() {
            const tick = (time) => {
                if (this.last !== null && ! this.paused && ! document.hidden) {
                    this.elapsed += time - this.last;
                    this.progress = Math.min(100, (this.elapsed / interval) * 100);
                    if (this.elapsed >= interval) {
                        this.next();
                    }
                }
                this.last = time;
                this.frame = requestAnimationFrame(tick);
            };
            this.frame = requestAnimationFrame(tick);
            if (this.reduced) {
                this.paused = true;
            }
        },
        destroy() {
            cancelAnimationFrame(this.frame);
        },
        go(index) {
            this.current = (index + count) % count;
            this.elapsed = 0;
            this.progress = 0;
        },
        next() { this.go(this.current + 1) },
        prev() { this.go(this.current - 1) },
        pause() { this.paused = true },
        resume() { this.paused = this.reduced },
        swipeStart(event) { this.startX = event.clientX },
        swipeEnd(event) {
            if (this.startX === null) {
                return;
            }
            const delta = event.clientX - this.startX;
            this.startX = null;
            if (Math.abs(delta) > 45) {
                delta < 0 ? this.next() : this.prev();
            }
        },
    }));

    /**
     * Lecteur code-barres / QR (douchette USB) : capte une saisie rapide terminée par Entrée.
     */
    window.Alpine.data('scanner', (callback) => ({
        buffer: '',
        last: 0,
        onKey(event) {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) {
                return;
            }
            const now = Date.now();
            if (now - this.last > 60) {
                this.buffer = '';
            }
            this.last = now;
            if (event.key === 'Enter' && this.buffer.length >= 4) {
                callback(this.buffer);
                this.buffer = '';
                return;
            }
            if (event.key.length === 1) {
                this.buffer += event.key;
            }
        },
    }));
});


/**
 * Requêtes Livewire : alimentent l'indicateur de synchronisation.
 */
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('request', ({ succeed, fail }) => {
        const sync = window.Alpine.store('sync');
        sync.pending++;
        succeed(() => {
            sync.pending = Math.max(0, sync.pending - 1);
            sync.reachable = true;
            sync.lastSync = Date.now();
        });
        fail(() => {
            sync.pending = Math.max(0, sync.pending - 1);
            if (! navigator.onLine) {
                sync.online = false;
            }
        });
    });
});

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    const assign = () => { window.Alpine.store('install').prompt = event; };
    window.Alpine ? assign() : document.addEventListener('alpine:init', assign);
});

window.addEventListener('appinstalled', () => {
    if (window.Alpine) {
        window.Alpine.store('install').installed = true;
    }
});

/**
 * Raccourcis clavier « application de bureau » :
 * Ctrl/⌘ + B replier le menu · Ctrl/⌘ + , paramètres · Ctrl/⌘ + Maj + F plein écran · Ctrl/⌘ + Maj + L changer de thème.
 */
document.addEventListener('keydown', (event) => {
    const mod = event.ctrlKey || event.metaKey;
    if (! mod || ! window.Alpine) {
        return;
    }
    const key = event.key.toLowerCase();
    if (key === 'b' && ! event.shiftKey) {
        event.preventDefault();
        window.Alpine.store('shell').toggleCollapsed();
    } else if (key === 'f' && event.shiftKey) {
        event.preventDefault();
        document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen?.();
    } else if (key === ',' && ! event.shiftKey) {
        const url = document.body.dataset.settingsUrl;
        if (url) {
            event.preventDefault();
            window.Livewire.navigate(url);
        }
    } else if (key === 'l' && event.shiftKey) {
        event.preventDefault();
        const dark = document.documentElement.classList.contains('dark');
        window.Alpine.evaluate(document.body, `$flux.appearance = '${dark ? 'light' : 'dark'}'`);
    }
});

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
