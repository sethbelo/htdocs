@php
    $displaySettings = [
        'resolution' => (string) \App\Models\Setting::get('display_resolution', 'auto'),
        'width' => (int) \App\Models\Setting::get('display_width', 1280),
        'height' => (int) \App\Models\Setting::get('display_height', 800),
        'scale' => (int) \App\Models\Setting::get('display_scale', 100),
        'density' => (string) \App\Models\Setting::get('display_density', 'comfortable'),
    ];
@endphp
<script>
    (() => {
        const allowedScales = [80, 90, 100, 110, 125];

        window.applyYebooxDisplaySettings = async (rawSettings = {}) => {
            const settings = rawSettings?.settings ?? rawSettings;
            const scale = allowedScales.includes(Number(settings?.scale)) ? Number(settings.scale) : 100;
            const density = settings?.density === 'compact' ? 'compact' : 'comfortable';
            const root = document.documentElement;

            root.dataset.ybDensity = density;
            root.dataset.ybDisplayScale = String(scale);

            if (window.yebooxDesktop?.applyDisplaySettings) {
                // Electron effectue le zoom natif et le redimensionnement de fenêtre.
                root.style.fontSize = '';
                try {
                    return await window.yebooxDesktop.applyDisplaySettings({ ...settings, scale, density });
                } catch (error) {
                    console.error('Application du profil d’affichage Electron impossible', error);
                }
            }

            // Dans un navigateur, le rem global fournit le même niveau de densité visuelle.
            root.style.fontSize = `${scale}%`;
            return { success: true, fallback: true, scale, density };
        };

        window.applyYebooxDisplaySettings(@js($displaySettings));

        document.addEventListener('livewire:init', () => {
            Livewire.on('display-settings-updated', (event) => {
                const payload = Array.isArray(event) ? (event[0] ?? {}) : event;
                window.applyYebooxDisplaySettings(payload);
            });
        });
    })();
</script>
