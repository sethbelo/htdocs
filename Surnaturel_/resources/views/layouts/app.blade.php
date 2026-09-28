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
    $departments = \App\Support\Navigation::departments($user);
    $current = \App\Support\Navigation::current($user);
    $badges = \App\Support\Navigation::badges($user);
@endphp
<body class="app-shell h-full font-sans antialiased" data-settings-url="{{ Route::has('settings.company') && $user->can('settings.view') ? route('settings.company') : '' }}" x-data :class="{ 'sidebar-collapsed': $store.shell.collapsed }">

    {{-- ═══ 1. BARRE LATÉRALE ═══════════════════════════════════════════════════
         Bureau : fixe, rétractable (icônes seules). Mobile : tiroir. ═══════════ --}}
    <div x-show="$store.shell.drawer" x-transition.opacity x-on:click="$store.shell.drawer = false" class="overlay z-30 lg:hidden" x-cloak></div>

    <aside class="sidebar" :data-open="$store.shell.drawer" aria-label="Navigation principale"
        x-data="{ switcher: false }" x-init="$store.nav.ensure(@js($current['department']['key'] ?? null))">
        {{-- Marque + sélecteur de département (« espace de travail ») --}}
        <div class="flex h-14 shrink-0 items-center justify-between gap-2 px-3">
            <a href="{{ route('launcher') }}" wire:navigate class="sidebar-brand" title="Toutes les applications">
                <x-brand-type class="sidebar-logo-full" />
                <x-brand-type variant="mono" class="sidebar-logo-mark" />
            </a>
            <button type="button" class="shell-icon-btn size-8 lg:hidden" x-on:click="$store.shell.drawer = false" aria-label="Fermer le menu"><flux:icon.x-mark variant="micro" /></button>
        </div>

        <div class="sidebar-extra relative px-3" x-on:click.outside="switcher = false" x-on:keydown.escape.window="switcher = false">
            <button type="button" x-on:click="switcher = true" class="nav-switcher group" :aria-expanded="switcher">
                <span class="size-8 shrink-0 rounded-lg bg-surface p-1 shadow-sm ring-1 ring-line dark:bg-white/8 dark:ring-white/10">{!! \App\Support\AppIcons::svg($current['department']['icon'] ?? 'launcher') !!}</span>
                <span class="min-w-0 flex-1 text-left leading-none">
                    <span class="block truncate text-[13px] font-semibold text-fg">{{ $current['department']['heading'] ?? 'Applications' }}</span>
                    <span class="mt-1 block truncate text-[11px] text-fg-subtle">{{ $current['item']['label'] ?? count($departments).' départements' }}</span>
                </span>
                <flux:icon.chevron-up-down variant="micro" class="size-4 shrink-0 text-fg-subtle transition group-hover:text-fg" />
            </button>
            <div x-show="switcher" x-cloak x-transition.origin.top class="nav-switcher-menu">
                @foreach ($departments as $department)
                    <a href="{{ route($department['items'][0]['route']) }}" wire:navigate x-on:click="switcher = false; $store.shell.drawer = false"
                        @class(['nav-switcher-option', 'is-current' => ($current['department']['key'] ?? null) === $department['key']])>
                        <span class="size-6 shrink-0">{!! \App\Support\AppIcons::svg($department['icon']) !!}</span>
                        <span class="flex-1 truncate">{{ $department['heading'] }}</span>
                        <span class="text-[11px] text-fg-subtle">{{ count($department['items']) }}</span>
                    </a>
                @endforeach
                <div class="my-1 h-px bg-line"></div>
                <a href="{{ route('launcher') }}" wire:navigate class="nav-switcher-option"><flux:icon.squares-2x2 variant="micro" class="size-4" /> Toutes les applications</a>
            </div>
        </div>

        <nav class="mt-2 flex flex-1 flex-col gap-4 overflow-y-auto px-3 pb-4 scrollbar-none">
            <div class="flex flex-col gap-0.5">
                <button type="button" x-on:click="$dispatch('open-palette')" class="sidebar-link group" title="Rechercher">
                    <flux:icon.magnifying-glass variant="micro" class="sidebar-glyph" /><span class="sidebar-label flex-1 text-left">Rechercher</span><kbd class="nav-kbd sidebar-label">⌘K</kbd>
                </button>
                <a href="{{ route('launcher') }}" wire:navigate class="sidebar-link group" @if (request()->routeIs('launcher')) aria-current="page" @endif title="Applications">
                    <flux:icon.squares-2x2 variant="micro" class="sidebar-glyph" /><span class="sidebar-label flex-1">Applications</span>
                </a>
                @foreach ([['dashboard', 'Tableau de bord', 'presentation-chart-line', 'dashboard.view'], ['approvals.index', 'Mes validations', 'check-badge', 'approvals.view'], ['tasks.index', 'Tâches', 'clipboard-document-check', 'tasks.view']] as [$route, $label, $icon, $permission])
                    @can($permission)
                        <a href="{{ route($route) }}" wire:navigate class="sidebar-link group" @if (request()->routeIs($route)) aria-current="page" @endif title="{{ $label }}" x-on:click="$store.shell.drawer = false">
                            <flux:icon :icon="$icon" variant="micro" class="sidebar-glyph" /><span class="sidebar-label flex-1">{{ $label }}</span>
                            @if (($badges[$route] ?? 0) > 0)<span class="nav-badge">{{ $badges[$route] }}</span>@endif
                        </a>
                    @endcan
                @endforeach
            </div>

            <div class="flex flex-col gap-0.5">
                <p class="sidebar-group"><span class="sidebar-group-text">Départements</span></p>
                @foreach ($departments as $department)
                    @php($isCurrent = ($current['department']['key'] ?? null) === $department['key'])
                    <div class="flex flex-col" wire:key="nav-{{ $department['key'] }}">
                        <button type="button" x-on:click="$store.nav.toggle('{{ $department['key'] }}')" @class(['sidebar-link sidebar-dept group', 'is-current' => $isCurrent]) title="{{ $department['heading'] }}">
                            <span class="size-[18px] shrink-0">{!! \App\Support\AppIcons::svg($department['icon']) !!}</span>
                            <span class="sidebar-label flex-1 truncate text-left">{{ $department['heading'] }}</span>
                            <flux:icon.chevron-right variant="micro" class="sidebar-label size-3.5 text-fg-subtle transition-transform duration-200" ::class="$store.nav.isOpen('{{ $department['key'] }}') && 'rotate-90'" />
                        </button>
                        <a href="{{ route($department['items'][0]['route']) }}" wire:navigate class="sidebar-rail-link" title="{{ $department['heading'] }}"><span class="size-5">{!! \App\Support\AppIcons::svg($department['icon']) !!}</span></a>
                        <div class="nav-children" :class="$store.nav.isOpen('{{ $department['key'] }}') && 'is-open'">
                            <div class="relative flex min-h-0 flex-col gap-0.5 overflow-hidden pt-0.5">
                                <span class="absolute top-1 bottom-1 left-[18px] border-l border-line"></span>
                                @foreach ($department['items'] as $item)
                                    <a href="{{ route($item['route']) }}" wire:navigate class="sidebar-link sidebar-child" @if ($isActive($item)) aria-current="page" @endif title="{{ $item['label'] }}" x-on:click="$store.shell.drawer = false">
                                        <span class="size-4 shrink-0">{!! \App\Support\AppIcons::svg($item['route']) !!}</span>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                        @if (($badges[$item['route']] ?? 0) > 0)<span class="nav-badge ms-auto">{{ $badges[$item['route']] }}</span>@endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </nav>

        <div class="mt-auto flex flex-col gap-0.5 border-t border-line p-3">
            @can('settings.view')
                <a href="{{ route('settings.company') }}" wire:navigate class="sidebar-link group" @if (request()->routeIs('settings.*')) aria-current="page" @endif title="Paramètres">
                    <flux:icon.cog-6-tooth variant="micro" class="sidebar-glyph" /><span class="sidebar-label flex-1">Paramètres</span><kbd class="nav-kbd sidebar-label">⌘,</kbd>
                </a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-link group w-full" title="Déconnexion">
                    <flux:icon.arrow-right-start-on-rectangle variant="micro" class="sidebar-glyph" /><span class="sidebar-label flex-1 text-left">Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ═══ 2. COLONNE PRINCIPALE ══════════════════════════════════════════════ --}}
    <div class="app-main bg-illustrated">
        <header class="topbar">
            <button type="button" class="shell-icon-btn lg:hidden" x-on:click="$store.shell.drawer = true" aria-label="Ouvrir le menu">
                <flux:icon.bars-3 variant="mini" />
            </button>
            <button type="button" class="shell-icon-btn size-9 max-lg:hidden" x-on:click="$store.shell.toggleCollapsed()" :aria-label="$store.shell.collapsed ? 'Déplier le menu' : 'Replier le menu'" title="Replier / déplier le menu (Ctrl/⌘ + B)">
                <svg x-show="! $store.shell.collapsed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="size-[18px]"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M16 15l-3-3 3-3"/></svg>
                <svg x-show="$store.shell.collapsed" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="size-[18px]"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M14 9l3 3-3 3"/></svg>
            </button>
            <nav class="hidden min-w-0 items-center gap-1.5 text-[13px] text-fg-subtle xl:flex" aria-label="Fil d’Ariane">
                <a href="{{ route('launcher') }}" wire:navigate class="truncate hover:text-fg">{{ \App\Models\Setting::get('company.name', 'Surnaturel Print') }}</a>
                @if ($current['department'])<span>/</span><span class="truncate">{{ $current['department']['heading'] }}</span>@endif
                <span>/</span><span class="truncate font-medium text-fg">{{ $current['item']['label'] ?? ($title ?? 'Applications') }}</span>
            </nav>

            <button type="button" x-on:click="$dispatch('open-palette')" class="ml-auto flex h-9 min-w-0 flex-1 items-center gap-2.5 rounded-lg border border-line bg-surface-2 px-3 text-left text-[13px] text-fg-subtle transition hover:border-line-strong sm:max-w-xs xl:max-w-64">
                <flux:icon.magnifying-glass variant="mini" class="size-4 shrink-0" />
                <span class="flex-1 truncate">Rechercher un écran, une action…</span>
                <span class="kbd max-sm:hidden">Ctrl K</span>
            </button>

            @include('partials.topbar-actions')
        </header>

        <main class="page space-y-6">
            {{ $slot }}
        </main>
    </div>

    @include('partials.shell-overlays')

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist

    @fluxScripts
</body>
</html>
