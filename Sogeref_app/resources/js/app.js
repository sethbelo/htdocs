/* =============================================================================
   SOGEREF ERP — JavaScript d'interface
   -----------------------------------------------------------------------------
   Alpine est fourni par Livewire 4 : on enregistre uniquement nos composants.
     1. Thème clair / sombre (persistant, synchronisé avec Electron)
     2. Coquille : barre latérale rétractable / tiroir tablette
     3. Palette de commandes (⌘K / Ctrl+K)
     4. Graphiques Chart.js aux couleurs SOGEREF
     5. Kanban glisser-déposer (SortableJS)
     6. Notifications (toasts)
   ============================================================================= */
import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';
import './personal';
import './communication';

window.Chart = Chart;

const BRAND = { navy: '#104a63', sun: '#ffce31', lagoon: '#3acece', flame: '#f7313a', slate: '#8a9dac', violet: '#8b5cf6', emerald: '#10b981' };
window.SOGEREF_COLORS = BRAND;

/* ─── 1. THÈME ──────────────────────────────────────────────────────────────── */
window.erpTheme = {
    get() {
        try { return localStorage.getItem('sogeref-theme') || 'light'; } catch { return 'light'; }
    },
    apply(mode) {
        const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        window.sogerefDesktop?.setTheme?.(mode);
        document.dispatchEvent(new CustomEvent('erp-theme-changed', { detail: { dark } }));
    },
    set(mode) {
        try { localStorage.setItem('sogeref-theme', mode); } catch {}
        this.apply(mode);
        return mode;
    },
    toggle() {
        return this.set(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    },
};

document.addEventListener('alpine:init', () => {
    /* ─── 2. COQUILLE ───────────────────────────────────────────────────────── */
    Alpine.store('shell', {
        drawer: false,
        collapsed: (() => { try { return localStorage.getItem('sogeref-sidebar') === '1'; } catch { return false; } })(),
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            try { localStorage.setItem('sogeref-sidebar', this.collapsed ? '1' : '0'); } catch {}
        },
    });

    /* ─── 3. PALETTE DE COMMANDES ───────────────────────────────────────────── */
    Alpine.data('commandPalette', (items = []) => ({
        open: false,
        query: '',
        active: 0,
        items,
        get filtered() {
            const q = this.query.trim().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            const list = q ? this.items.filter(i => (i.label + ' ' + i.group).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').includes(q)) : this.items;
            return list.slice(0, 12);
        },
        show() { this.open = true; this.query = ''; this.active = 0; this.$nextTick(() => this.$refs.search?.focus()); },
        move(step) { this.active = (this.active + step + this.filtered.length) % Math.max(1, this.filtered.length); },
        go(item = this.filtered[this.active]) { if (item) { this.open = false; Livewire.navigate(item.url); } },
    }));

    /* ─── 4. GRAPHIQUES ─────────────────────────────────────────────────────── */
    Alpine.data('erpChart', (config) => ({
        chart: null,
        init() {
            this.render();
            this._onTheme = () => this.render();
            document.addEventListener('erp-theme-changed', this._onTheme);
        },
        render() {
            const dark = document.documentElement.classList.contains('dark');
            const grid = dark ? 'rgba(168,188,203,.10)' : 'rgba(16,74,99,.07)';
            const tick = dark ? '#8aa3b3' : '#8a9dac';
            Chart.getChart(this.$refs.canvas)?.destroy();
            const isCircular = ['doughnut', 'pie'].includes(config.type);
            this.chart = new Chart(this.$refs.canvas, {
                type: config.type ?? 'bar',
                data: {
                    labels: config.labels,
                    datasets: config.datasets.map((d, i) => ({
                        borderWidth: config.type === 'line' ? 2.25 : 0,
                        borderRadius: config.type === 'bar' ? 6 : 0,
                        maxBarThickness: 34,
                        tension: .35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        fill: d.fill ?? false,
                        ...d,
                        borderColor: d.borderColor ?? d.color ?? Object.values(BRAND)[i],
                        backgroundColor: d.backgroundColor ?? (isCircular ? d.colors : (d.fill ? (d.color ?? BRAND.navy) + '18' : d.color ?? Object.values(BRAND)[i])),
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: isCircular ? '72%' : undefined,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: config.legend ?? false, position: 'bottom', labels: { color: tick, boxWidth: 8, boxHeight: 8, usePointStyle: true, font: { size: 11, weight: 600 } } },
                        tooltip: { backgroundColor: '#061a24', padding: 10, cornerRadius: 8, titleFont: { weight: 700 }, callbacks: config.money ? { label: c => ` ${c.dataset.label ?? c.label} : ${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(c.raw)} $` } : {} },
                    },
                    scales: isCircular ? {} : {
                        x: { stacked: config.stacked, grid: { display: false }, border: { display: false }, ticks: { color: tick, font: { size: 11 } } },
                        y: { stacked: config.stacked, grid: { color: grid }, border: { display: false }, ticks: { color: tick, font: { size: 11 }, callback: v => Math.abs(v) >= 1000 ? `${v / 1000}k` : v } },
                    },
                },
            });
        },
        destroy() { document.removeEventListener('erp-theme-changed', this._onTheme); this.chart?.destroy(); },
    }));

    /* ─── 5. KANBAN ─────────────────────────────────────────────────────────── */
    // <div x-data="kanban('moveCard')"> … <div data-column="stage" x-ref…> <article data-id="12">
    Alpine.data('kanban', (method = 'move') => ({
        init() {
            this.$root.querySelectorAll('[data-column]').forEach((column) => {
                Sortable.create(column, {
                    group: 'kanban',
                    animation: 160,
                    ghostClass: 'sortable-ghost',
                    delay: 120,
                    delayOnTouchOnly: true,
                    onEnd: (event) => {
                        const ids = [...event.to.querySelectorAll('[data-id]')].map(el => Number(el.dataset.id));
                        this.$wire.call(method, Number(event.item.dataset.id), event.to.dataset.column, ids);
                    },
                });
            });
        },
    }));

    /* ─── 6. NOTIFICATIONS ──────────────────────────────────────────────────── */
    Alpine.data('toaster', () => ({
        toasts: [],
        push(detail) {
            const payload = Array.isArray(detail) ? detail[0] : detail;
            const toast = { id: Date.now() + Math.random(), type: payload.type ?? 'success', message: payload.message ?? payload };
            this.toasts.push(toast);
            setTimeout(() => this.remove(toast.id), toast.type === 'error' ? 6500 : 4000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
    }));
});

// Le thème est réappliqué après chaque navigation wire:navigate.
document.addEventListener('livewire:navigated', () => window.erpTheme.apply(window.erpTheme.get()));
