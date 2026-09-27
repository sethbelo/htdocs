<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'PDV' }} — Yeboox POS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (() => {
            const preference = localStorage.getItem('yeboox.appearance') || 'system';
            const isDark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            localStorage.setItem('yeboox.appearance', preference);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
            preference === 'system'
                ? localStorage.removeItem('flux.appearance')
                : localStorage.setItem('flux.appearance', preference);
        })();
    </script>
    @fluxAppearance
    @livewireStyles
</head>
<body
    class="bg-base-200 text-base-content antialiased overflow-hidden"
    x-data="{
        themeMode: localStorage.getItem('yeboox.appearance') || 'system',
        setTheme(mode) {
            if (! ['light', 'dark', 'system'].includes(mode)) return;
            this.themeMode = mode;
            localStorage.setItem('yeboox.appearance', mode);
            const isDark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
            mode === 'system' ? localStorage.removeItem('flux.appearance') : localStorage.setItem('flux.appearance', mode);
            if (window.Flux && 'appearance' in window.Flux) window.Flux.appearance = mode;
        }
    }"
    x-init="setTheme(themeMode)"
>
    <div class="h-screen flex flex-col">
        {{ $slot }}
    </div>
    @livewireScripts
    @fluxScripts
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
