<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    @include('partials.head')
</head>
@php
    $user = auth()->user();
    $palette = \App\Support\Navigation::flat($user);
@endphp
{{-- Lanceur d'applications plein écran (sans menu latéral) --}}
<body class="launcher-shell bg-illustrated min-h-dvh font-sans antialiased" data-settings-url="{{ $user->can('settings.view') ? route('settings.company') : '' }}">
    <header class="launcher-topbar">
        <a href="{{ route('launcher') }}" wire:navigate class="sidebar-brand shrink-0"><x-brand-type /></a>
        <button type="button" x-data x-on:click="$dispatch('open-palette')" class="ml-auto flex h-9 min-w-0 max-w-64 flex-1 items-center gap-2.5 rounded-xl border border-line/80 bg-surface/60 px-3 text-left text-[13px] text-fg-subtle backdrop-blur transition hover:border-line-strong max-sm:hidden">
            <flux:icon.magnifying-glass variant="mini" class="size-4 shrink-0" />
            <span class="flex-1 truncate">Rechercher…</span>
            <span class="kbd">Ctrl K</span>
        </button>
        @include('partials.topbar-actions')
    </header>

    <main class="launcher-main">
        {{ $slot }}
    </main>

    @include('partials.shell-overlays')

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist

    @fluxScripts
</body>
</html>
