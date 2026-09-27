<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Yeboox') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-950 font-sans antialiased">
    <flux:accent color="violet" />

    <div class="fixed right-4 top-4 z-50">
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="subtle" icon="swatch" square tooltip="Changer l’apparence" />
            <flux:menu>
                <flux:menu.radio.group x-data x-model="$flux.appearance">
                    <flux:menu.radio value="light" icon="sun">Clair</flux:menu.radio>
                    <flux:menu.radio value="dark" icon="moon">Sombre</flux:menu.radio>
                    <flux:menu.radio value="system" icon="computer-desktop">Système</flux:menu.radio>
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    </div>

    {{ $slot }}

    @livewireScripts
    @fluxScripts
</body>
</html>
