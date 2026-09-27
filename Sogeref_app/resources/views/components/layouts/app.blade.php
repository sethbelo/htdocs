<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    {{-- ═══ 1. EN-TÊTE HTML ══════════════════════════════════════════════════ --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104A63">
    <title>{{ isset($title) ? $title.' · ' : '' }}SOGEREF ERP</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('brand/icon-192.png') }}">
    {{-- Thème appliqué avant le premier rendu : aucun flash blanc en mode sombre. --}}
    <script>
        (() => { try { const m = localStorage.getItem('sogeref-theme') || 'light'; if (m === 'dark' || (m === 'system' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {} })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    $navigation = \App\Support\Modules::navigation($user);
    $approvals = \App\Support\Approvals::for($user);
    $pendingTotal = array_sum(array_column($approvals, 'count'));
    $groups = config('erp.groups');
    $isActive = function (array $module) {
        $route = $module['route'];
        $prefix = \Illuminate\Support\Str::beforeLast($route, '.');
        return request()->routeIs($route) || request()->routeIs($route.'.*') || ($prefix !== $route && request()->routeIs($prefix.'.*'));
    };
    $palette = $navigation->flatten(1)->map(fn ($m) => ['label' => $m['label'], 'group' => $groups[$m['group']] ?? '', 'url' => route($m['route'])])
        ->merge(collect([
            ['label' => 'Nouveau devis', 'group' => 'Action rapide', 'route' => 'quotes.create', 'module' => 'quotes'],
            ['label' => 'Nouveau bon (achat, décaissement, sortie)', 'group' => 'Action rapide', 'route' => 'vouchers', 'module' => 'vouchers'],
            ['label' => 'Ouvrir la caisse', 'group' => 'Action rapide', 'route' => 'cash', 'module' => 'cash'],
            ['label' => 'Faire l’appel du jour (présences)', 'group' => 'Action rapide', 'route' => 'attendance', 'module' => 'attendance'],
            ['label' => 'Enregistrer un visiteur', 'group' => 'Action rapide', 'route' => 'reception', 'module' => 'reception'],
        ])->filter(fn ($a) => $user?->canAccessModule($a['module']))->map(fn ($a) => ['label' => $a['label'], 'group' => $a['group'], 'url' => route($a['route'])]))
        ->values();
@endphp
<body class="app-shell h-full" x-data :class="{ 'sidebar-collapsed': $store.shell.collapsed, 'overflow-hidden lg:overflow-auto': $store.shell.drawer }">

    {{-- ═══ 2. BARRE LATÉRALE ═════════════════════════════════════════════════
         Bureau : fixe, rétractable (icônes seules). Tablette : tiroir. --}}
    <div x-show="$store.shell.drawer" x-transition.opacity x-on:click="$store.shell.drawer = false" class="overlay z-30 lg:hidden" x-cloak></div>

    <aside class="sidebar" :data-open="$store.shell.drawer" aria-label="Navigation principale">
        <div class="flex h-16 shrink-0 items-center justify-between gap-2 px-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
                <x-ui.mark class="size-9 shrink-0 rounded-[11px] ring-1 ring-white/15" />
                <span class="sidebar-label min-w-0 leading-none">
                    <span class="block font-display text-[19px] font-bold tracking-wide">SOGEREF</span>
                    <span class="mt-1 block text-[10px] font-semibold tracking-[.2em] text-sun-300 uppercase">ERP · Sarl</span>
                </span>
            </a>
            <button type="button" class="btn btn-sm btn-icon text-white/70 hover:bg-white/10 lg:hidden" x-on:click="$store.shell.drawer = false" aria-label="Fermer le menu"><x-ui.icon name="x-mark" /></button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-4 scrollbar-none">
            @foreach ($navigation as $group => $modules)
                <p class="sidebar-group"><span class="sidebar-group-text">{{ $groups[$group] ?? $group }}</span></p>
                <ul class="space-y-0.5">
                    @foreach ($modules as $module)
                        <li>
                            <a href="{{ route($module['route']) }}" wire:navigate class="sidebar-link" @if ($isActive($module)) aria-current="page" @endif
                               title="{{ $module['label'] }}" x-on:click="$store.shell.drawer = false">
                                <x-ui.icon :name="$module['icon']" class="size-[19px] opacity-90" />
                                <span class="sidebar-label truncate">{{ $module['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="sidebar-extra border-t border-white/10 p-3">
            <div class="flex items-center gap-3 rounded-xl bg-white/6 p-2.5">
                <x-ui.avatar :name="$user->name" size="sm" tone="bg-sun-400 text-brand-900" />
                <div class="min-w-0 text-[12px] leading-tight">
                    <p class="truncate font-semibold">{{ $user->name }}</p>
                    <p class="truncate text-white/55">{{ $user->roleLabel() }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- ═══ 3. COLONNE PRINCIPALE ═════════════════════════════════════════════ --}}
    <div class="app-main">
        {{-- 3.1 Barre supérieure --}}
        <header class="topbar">
            <button type="button" class="btn btn-ghost btn-icon lg:hidden" x-on:click="$store.shell.drawer = true" aria-label="Ouvrir le menu"><x-ui.icon name="bars-3" /></button>
            <button type="button" class="btn btn-ghost btn-icon max-xl:hidden" x-on:click="$store.shell.toggleCollapsed()" :aria-label="$store.shell.collapsed ? 'Déplier le menu' : 'Replier le menu'" title="Replier / déplier le menu">
                <x-ui.icon name="bars-3-bottom-left" />
            </button>

            <button type="button" x-on:click="$dispatch('open-palette')" class="flex h-10 w-full max-w-md items-center gap-2.5 rounded-lg border border-line bg-surface-2 px-3 text-left text-[13.5px] text-fg-subtle transition hover:border-line-strong">
                <x-ui.icon name="magnifying-glass" class="size-4" />
                <span class="flex-1 truncate">Rechercher un module, une action…</span>
                <span class="kbd max-sm:hidden">Ctrl K</span>
            </button>

            <div class="ml-auto flex items-center gap-1">
                <span class="mr-2 hidden text-right text-[12px] leading-tight xl:block">
                    <span class="block font-semibold text-fg">{{ company()->name }}</span>
                    <span class="block text-fg-subtle">{{ $user->currentBranch?->name ?? 'Siège Kinshasa' }}</span>
                </span>

                {{-- Approbations en attente --}}
                <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                    <button type="button" class="btn btn-ghost btn-icon relative" x-on:click="open = !open" aria-label="À traiter">
                        <x-ui.icon name="bell" />
                        @if ($pendingTotal)
                            <span class="absolute top-1.5 right-1.5 grid min-w-4 place-items-center rounded-full bg-flame-500 px-1 text-[10px] leading-4 font-bold text-white">{{ $pendingTotal > 99 ? '99+' : $pendingTotal }}</span>
                        @endif
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak class="card absolute right-0 z-40 mt-2 w-80 overflow-hidden p-0 shadow-[var(--shadow-pop)]">
                        <p class="border-b border-line px-4 py-3 font-display text-[14px] font-semibold">À traiter</p>
                        @forelse ($approvals as $item)
                            <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 px-4 py-3 text-[13.5px] transition hover:bg-surface-2">
                                <span class="grid size-8 place-items-center rounded-lg bg-sun-100 text-sun-800 dark:bg-sun-400/15 dark:text-sun-300"><x-ui.icon :name="$item['icon']" class="size-4" /></span>
                                <span class="flex-1">{{ $item['label'] }}</span>
                                <span class="badge tone-danger">{{ $item['count'] }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-[13px] text-fg-subtle">Rien en attente. Tout est à jour ✓</p>
                        @endforelse
                    </div>
                </div>

                {{-- Bascule thème : lune (passer en sombre) / soleil (passer en clair) --}}
                <button type="button" class="btn btn-ghost btn-icon" x-data="{ dark: document.documentElement.classList.contains('dark') }"
                        x-on:erp-theme-changed.document="dark = $event.detail.dark" x-on:click="window.erpTheme.toggle()" :aria-label="dark ? 'Passer en thème clair' : 'Passer en thème sombre'" :title="dark ? 'Thème clair' : 'Thème sombre'">
                    <span x-show="!dark"><x-ui.icon name="moon" /></span>
                    <span x-show="dark" x-cloak><x-ui.icon name="sun" class="text-sun-400" /></span>
                </button>

                @if ($user->canAccessModule('messages'))
                    <a href="{{ route('chat') }}" wire:navigate class="btn btn-ghost btn-icon max-sm:hidden" aria-label="Messagerie"><x-ui.icon name="chat-bubble-left-right" /></a>
                @endif

                {{-- Menu utilisateur --}}
                <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                    <button type="button" class="ml-1 flex items-center gap-2 rounded-full p-0.5 transition hover:ring-4 hover:ring-brand-100 dark:hover:ring-white/10" x-on:click="open = !open" aria-label="Mon compte">
                        <x-ui.avatar :name="$user->name" size="sm" tone="bg-brand-600 text-white" />
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak class="card absolute right-0 z-40 mt-2 w-64 p-1.5 shadow-[var(--shadow-pop)]">
                        <div class="px-3 py-2.5">
                            <p class="truncate font-semibold">{{ $user->name }}</p>
                            <p class="truncate text-[12px] text-fg-subtle">{{ $user->email }}</p>
                            <span class="badge tone-brand mt-2">{{ $user->roleLabel() }}</span>
                        </div>
                        <div class="divider my-1"></div>
                        @if ($user->canAccessModule('system'))
                            <a href="{{ route('system') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13.5px] hover:bg-surface-2"><x-ui.icon name="cog-8-tooth" class="size-4 text-fg-subtle" /> Master système</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-[13.5px] text-flame-600 hover:bg-flame-50 dark:hover:bg-flame-400/10"><x-ui.icon name="arrow-right-start-on-rectangle" class="size-4" /> Se déconnecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- 3.2 Contenu de la page --}}
        <main class="page">{{ $slot }}</main>
    </div>

    {{-- ═══ 4. PALETTE DE COMMANDES (Ctrl/⌘ + K) ═════════════════════════════ --}}
    <div x-data="commandPalette(@js($palette))" x-on:open-palette.window="show()" x-on:keydown.window.ctrl.k.prevent="show()" x-on:keydown.window.meta.k.prevent="show()" x-on:keydown.escape.window="open = false">
        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center p-4 pt-[12vh]">
            <div class="overlay" x-on:click="open = false"></div>
            <div x-show="open" x-transition.scale.98 class="modal max-w-xl overflow-hidden">
                <div class="flex items-center gap-3 border-b border-line px-4">
                    <x-ui.icon name="magnifying-glass" class="text-fg-subtle" />
                    <input x-ref="search" x-model="query" x-on:input="active = 0" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                           class="h-14 flex-1 border-0 bg-transparent text-[15px] outline-none placeholder:text-fg-subtle" placeholder="Aller à… (commandes, paie, caisse…)">
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

    {{-- ═══ 5. NOTIFICATIONS ══════════════════════════════════════════════════
         Livewire : $this->dispatch('toast', type: 'success', message: '…') --}}
    <div x-data="toaster" x-on:toast.window="push($event.detail)" x-init="@if (session('toast')) push(@js(session('toast'))) @endif" class="pointer-events-none fixed right-4 bottom-4 z-[70] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition class="pointer-events-auto flex items-start gap-3 rounded-xl border border-line bg-surface p-3.5 shadow-[var(--shadow-pop)]">
                <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full" :class="toast.type === 'error' ? 'bg-flame-50 text-flame-600' : (toast.type === 'warning' ? 'bg-sun-100 text-sun-800' : 'bg-emerald-50 text-emerald-600')">
                    <svg x-show="toast.type !== 'error'" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                    <svg x-show="toast.type === 'error'" class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 00-1.4 1.4L8.58 10l-1.3 1.3a1 1 0 101.42 1.4L10 11.42l1.3 1.3a1 1 0 001.4-1.42L11.42 10l1.3-1.3a1 1 0 00-1.42-1.4L10 8.58 8.7 7.3z" clip-rule="evenodd"/></svg>
                </span>
                <p class="flex-1 text-[13.5px] font-medium text-fg" x-text="toast.message"></p>
                <button type="button" x-on:click="remove(toast.id)" class="text-fg-subtle hover:text-fg" aria-label="Fermer">×</button>
            </div>
        </template>
    </div>
</body>
</html>
