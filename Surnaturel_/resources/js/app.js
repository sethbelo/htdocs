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
            Chart.defaults.color = dark ? '#b3b3d1' : '#56567d';
            Chart.defaults.borderColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(30,27,75,0.06)';
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
