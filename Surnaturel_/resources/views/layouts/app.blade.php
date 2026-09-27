<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    @include('partials.head')
</head>
@php
    $user = auth()->user();
    $roleLabel = $user->getRoleNames()->map(fn (string $role) => config("erp.roles.{$role}.label", $role))->first();
    $navigation = \App\Support\Navigation::for($user);
    $palette = \App\Support\Navigation::flat($user);
    $isActive = fn (array $item): bool => request()->routeIs($item['match'] ?? $item['route']);
@endphp
<body class="app-shell h-full font-sans antialiased" x-data :class="{ 'sidebar-collapsed': $store.shell.collapsed }">

    {{-- ═══ 1. BARRE LATÉRALE ═══════════════════════════════════════════════════
         Bureau : fixe, rétractable (icônes seules). Mobile : tiroir. ═══════════ --}}
    <div x-show="$store.shell.drawer" x-transition.opacity x-on:click="$store.shell.drawer = false" class="overlay z-30 lg:hidden" x-cloak></div>

    <aside class="sidebar" :data-open="$store.shell.drawer" aria-label="Navigation principale">
        <div class="flex h-16 shrink-0 items-center justify-between gap-2 px-4">
            <a href="{{ route('home') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
                <x-app-logo class="size-9 shrink-0 rounded-[11px] ring-1 ring-white/15" />
                <span class="sidebar-label min-w-0 leading-none">
                    <span class="block truncate font-display text-[16px] font-bold tracking-wide">{{ \App\Models\Setting::get('company.name', 'ERP Imprimerie') }}</span>
                    <span class="mt-1 block text-[10px] font-semibold tracking-[.2em] text-safran-300 uppercase">Imprimerie · Boutique</span>
                </span>
            </a>
            <button type="button" class="shell-icon-btn text-white/70 hover:bg-white/10 hover:text-white lg:hidden" x-on:click="$store.shell.drawer = false" aria-label="Fermer le menu">
                <flux:icon.x-mark variant="micro" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-4 scrollbar-none">
            @foreach ($navigation as $group)
                <p class="sidebar-group"><span class="sidebar-group-text">{{ $group['heading'] }}</span></p>
                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ route($item['route']) }}" wire:navigate class="sidebar-link" @if ($isActive($item)) aria-current="page" @endif
                               title="{{ $item['label'] }}" x-on:click="$store.shell.drawer = false">
                                <flux:icon :icon="$item['icon']" variant="mini" class="size-[19px] shrink-0 opacity-90" />
                                <span class="sidebar-label truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="sidebar-extra border-t border-white/10 p-3">
            <flux:dropdown position="top" align="start" class="w-full">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl bg-white/6 p-2.5 text-left transition hover:bg-white/10">
                    <span class="avatar size-9 shrink-0 bg-safran-400 text-[12px] text-brand-900">{{ $user->initials() }}</span>
                    <span class="min-w-0 flex-1 text-[12px] leading-tight">
                        <span class="block truncate font-semibold">{{ $user->name }}</span>
                        <span class="block truncate text-white/55">{{ $roleLabel }}</span>
                    </span>
                    <flux:icon.chevron-up-down variant="micro" class="size-4 shrink-0 text-white/40" />
                </button>
                <x-user-menu :user="$user" />
            </flux:dropdown>
        </div>
    </aside>

    {{-- ═══ 2. COLONNE PRINCIPALE ══════════════════════════════════════════════ --}}
    <div class="app-main">
        <header class="topbar">
            <button type="button" class="shell-icon-btn lg:hidden" x-on:click="$store.shell.drawer = true" aria-label="Ouvrir le menu">
                <flux:icon.bars-3 variant="mini" />
            </button>
            <button type="button" class="shell-icon-btn max-lg:hidden" x-on:click="$store.shell.toggleCollapsed()" :aria-label="$store.shell.collapsed ? 'Déplier le menu' : 'Replier le menu'" title="Replier / déplier le menu">
                <flux:icon.bars-3-bottom-left variant="mini" />
            </button>

            <button type="button" x-on:click="$dispatch('open-palette')" class="flex h-10 w-full max-w-xs items-center gap-2.5 rounded-lg border border-line bg-surface-2 px-3 text-left text-[13.5px] text-fg-subtle transition hover:border-line-strong sm:max-w-md">
                <flux:icon.magnifying-glass variant="mini" class="size-4 shrink-0" />
                <span class="flex-1 truncate">Rechercher un écran, une action…</span>
                <span class="kbd max-sm:hidden">Ctrl K</span>
            </button>

            <div class="ml-auto flex items-center gap-0.5 sm:gap-1">
                @if ($user->hasConsolidatedView())
                    <flux:badge size="sm" color="indigo" icon="globe-alt" class="mr-1 max-sm:hidden">Vue consolidée</flux:badge>
                @else
                    <flux:badge size="sm" color="zinc" icon="map-pin" class="mr-1 max-sm:hidden">{{ $user->sites->pluck('name')->implode(' · ') ?: 'Aucun siège' }}</flux:badge>
                @endif

                <livewire:notifications-bell />

                <flux:dropdown position="bottom" align="end" class="max-sm:hidden">
                    <button type="button" class="shell-icon-btn" aria-label="Apparence">
                        <flux:icon.swatch variant="mini" />
                    </button>
                    <flux:menu>
                        <flux:menu.radio.group x-data variant="segmented" x-model="$flux.appearance">
                            <flux:menu.radio value="light" icon="sun">Clair</flux:menu.radio>
                            <flux:menu.radio value="dark" icon="moon">Sombre</flux:menu.radio>
                            <flux:menu.radio value="system" icon="computer-desktop">Auto</flux:menu.radio>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="ml-1 rounded-full p-0.5 transition hover:ring-4 hover:ring-brand-100 dark:hover:ring-white/10" aria-label="Mon compte">
                        <span class="avatar size-8 bg-brand-600 text-[11px] text-white">{{ $user->initials() }}</span>
                    </button>
                    <x-user-menu :user="$user" />
                </flux:dropdown>
            </div>
        </header>

        <main class="page space-y-6">
            {{ $slot }}
        </main>
    </div>

    {{-- ═══ 3. PALETTE DE COMMANDES (Ctrl/⌘ + K) ══════════════════════════════ --}}
    <div x-data="commandPalette(@js($palette))" x-on:open-palette.window="show()" x-on:keydown.window.ctrl.k.prevent="show()" x-on:keydown.window.meta.k.prevent="show()" x-on:keydown.escape.window="open = false">
        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center p-4 pt-[12vh]">
            <div class="overlay" x-on:click="open = false"></div>
            <div x-show="open" x-transition.scale.98 class="modal max-w-xl overflow-hidden">
                <div class="flex items-center gap-3 border-b border-line px-4">
                    <flux:icon.magnifying-glass variant="mini" class="text-fg-subtle" />
                    <input x-ref="search" x-model="query" x-on:input="active = 0" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                           class="h-14 flex-1 border-0 bg-transparent text-[15px] outline-none placeholder:text-fg-subtle" placeholder="Aller à… (commandes, caisse, stock…)">
                    <span class="kbd">Échap</span>
                </div>
                <ul class="max-h-[50vh] overflow-y-auto p-2">
                    <template x-for="(item, index) in filtered" :key="item.url + item.label">
                        <li>
                            <button type="button" x-on:click="go(item)" x-on:mouseenter="active = index" :class="index === active ? 'bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-white' : ''" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-[14px]">
                                <span x-text="item.label" class="font-medium"></span>
                                <span x-text="item.group" class="text-[11.5px] text-fg-subtle"></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-3 py-8 text-center text-[13px] text-fg-subtle">Aucun résultat.</li>
                </ul>
            </div>
        </div>
    </div>

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist

    @fluxScripts
</body>
</html>
