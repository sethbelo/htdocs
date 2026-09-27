<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Yeboox') }}</title>
        <meta name="description" content="Sign in to your Yeboox account — powerful tools for modern teams.">

        <!-- Fonts — Inter for premium UI -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        {{-- Prevent FOUC: apply dark class before first paint --}}
        <script>
            (function() {
                const stored = localStorage.getItem('yeboox-theme');
                if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="antialiased" style="font-family: 'Inter', system-ui, sans-serif;">
        <div class="text-gray-900 dark:text-gray-100">
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
