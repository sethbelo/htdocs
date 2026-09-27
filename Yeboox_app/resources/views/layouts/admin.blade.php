<!DOCTYPE html>
@php
    $uiEstablishment = auth()->user()?->establishment;
    $uiThemeMode = $uiEstablishment?->getSetting('theme_mode', 'system') ?? 'system';
    $uiThemeColor = $uiEstablishment?->getSetting('theme_color', '#2563eb') ?? '#2563eb';
    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $uiThemeColor)) $uiThemeColor = '#2563eb';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $uiThemeMode === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Yeboox POS — gestion commerciale moderne">
    <meta name="theme-color" content="#18181b">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <title>{{ $title ?? 'Administration' }} — {{ config('app.name', 'Yeboox POS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (() => {
            const companyDefault = @json($uiThemeMode);
            const preference = localStorage.getItem('yeboox.appearance') || companyDefault;
            const isDark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            localStorage.setItem('yeboox.appearance', preference);
            document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
            preference === 'system'
                ? localStorage.removeItem('flux.appearance')
                : localStorage.setItem('flux.appearance', preference);
        })();
    </script>
    @fluxAppearance
    @livewireStyles
    <style>:root { --yeboox-brand: {{ $uiThemeColor }}; }</style>
</head>
<body
    class="min-h-screen bg-zinc-50 dark:bg-zinc-950"
    x-data="{
        themeMode: localStorage.getItem('yeboox.appearance') || 'system',
        setTheme(mode) {
            if (! ['light', 'dark', 'system'].includes(mode)) return;
            this.themeMode = mode;
            localStorage.setItem('yeboox.appearance', mode);
            const isDark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
            if (mode === 'system') {
                localStorage.removeItem('flux.appearance');
                document.documentElement.classList.toggle('dark', isDark);
            } else {
                localStorage.setItem('flux.appearance', mode);
                document.documentElement.classList.toggle('dark', mode === 'dark');
            }
            if (window.Flux && 'appearance' in window.Flux) window.Flux.appearance = mode;
        }
    }"
    x-init="setTheme(themeMode)"
    x-on:yeboox-theme-changed.window="setTheme($event.detail.mode)"
>
    <flux:sidebar sticky collapsible class="yeboox-sidebar border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="yeboox-sidebar-art" aria-hidden="true">
            <svg viewBox="0 0 320 920" preserveAspectRatio="xMidYMid slice" role="presentation">
                <defs>
                    <pattern id="sidebar-light-grid" width="28" height="28" patternUnits="userSpaceOnUse"><path d="M28 0H0V28" fill="none" stroke="currentColor" stroke-width="1"/></pattern>
                    <linearGradient id="sidebar-light-fade" x1="0" y1="0" x2="0" y2="1"><stop stop-color="white" stop-opacity="0"/><stop offset="1" stop-color="white" stop-opacity="1"/></linearGradient>
                    <mask id="sidebar-light-mask"><rect width="320" height="920" fill="url(#sidebar-light-fade)"/></mask>
                    <radialGradient id="sidebar-dark-glow"><stop stop-color="#2563eb" stop-opacity=".5"/><stop offset="1" stop-color="#2563eb" stop-opacity="0"/></radialGradient>
                </defs>
                <g class="yeboox-sidebar-light-shape">
                    <rect width="320" height="920" fill="url(#sidebar-light-grid)" mask="url(#sidebar-light-mask)"/>
                    <circle cx="270" cy="780" r="132" fill="none" stroke="currentColor" stroke-width="22" opacity=".18"/>
                    <path d="M-30 695C72 625 142 742 350 610" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="6 11"/>
                </g>
                <g class="yeboox-sidebar-dark-shape">
                    <circle cx="72" cy="180" r="185" fill="url(#sidebar-dark-glow)"/>
                    <path d="M-64 690C58 560 158 835 384 608" fill="none" stroke="currentColor" stroke-width="2" opacity=".7"/>
                    <path d="M-42 730C90 600 178 858 366 662" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="4 12" opacity=".45"/>
                    <circle cx="256" cy="760" r="92" fill="none" stroke="currentColor" stroke-width="1" opacity=".3"/>
                    <circle cx="256" cy="760" r="58" fill="none" stroke="currentColor" stroke-width="12" opacity=".12"/>
                </g>
            </svg>
        </div>
        <div class="flex items-center gap-3 px-2 py-2">
            @if($uiEstablishment?->logo_url)
                <img src="{{ $uiEstablishment->logo_url }}" alt="Logo" class="size-10 shrink-0 rounded-xl object-contain ring-1 ring-zinc-200 dark:ring-zinc-700">
            @else
                <div class="grid size-10 shrink-0 place-items-center rounded-xl text-sm font-bold text-white shadow-sm" style="background: var(--yeboox-brand)">Y</div>
            @endif
            <div class="min-w-0 in-data-flux-sidebar-collapsed-desktop:hidden">
                <flux:heading size="lg">Yeboox POS</flux:heading>
                <flux:text size="sm" class="truncate">{{ auth()->user()?->establishment?->name ?? 'Espace de gestion' }}</flux:text>
            </div>
        </div>

        <flux:sidebar.nav class="mt-3 space-y-1">
            <flux:sidebar.item icon="squares-2x2" href="{{ route('admin.dashboard') }}" :current="request()->routeIs('admin.dashboard')">Tableau de bord</flux:sidebar.item>
            @can('pos.access')
                <flux:sidebar.item icon="computer-desktop" href="{{ route('pos.terminal') }}" :current="request()->routeIs('pos.*')">Point de vente</flux:sidebar.item>
            @endcan
            @if($uiEstablishment?->kitchenModuleEnabled())
                @can('kitchen.view')
                    <flux:sidebar.item icon="fire" href="{{ route('kitchen.display') }}" :current="request()->routeIs('kitchen.*')">Cuisine</flux:sidebar.item>
                @endcan
            @endif

            <flux:sidebar.group heading="Commerce" expandable :expanded="request()->routeIs('admin.products.*', 'admin.categories.*', 'admin.customers.*', 'admin.suppliers.*')">
                @can('products.view')
                    <flux:sidebar.item icon="cube" href="{{ route('admin.products.index') }}" :current="request()->routeIs('admin.products.*')">Produits</flux:sidebar.item>
                @endcan
                @can('categories.view')
                    <flux:sidebar.item icon="tag" href="{{ route('admin.categories.index') }}" :current="request()->routeIs('admin.categories.*')">Catégories</flux:sidebar.item>
                @endcan
                @can('customers.view')
                    <flux:sidebar.item icon="users" href="{{ route('admin.customers.index') }}" :current="request()->routeIs('admin.customers.*')">Clients</flux:sidebar.item>
                @endcan
                @can('suppliers.view')
                    <flux:sidebar.item icon="truck" href="{{ route('admin.suppliers.index') }}" :current="request()->routeIs('admin.suppliers.*')">Fournisseurs</flux:sidebar.item>
                @endcan
            </flux:sidebar.group>

            <flux:sidebar.group heading="Opérations" expandable :expanded="request()->routeIs('admin.stocks.*', 'admin.tables.*', 'admin.reservations.*', 'admin.appointments.*', 'admin.orders.*', 'admin.cash-sessions.*')">
                @can('stocks.view')
                    <flux:sidebar.item icon="archive-box" href="{{ route('admin.stocks.index') }}" :current="request()->routeIs('admin.stocks.*')">Stocks</flux:sidebar.item>
                @endcan
                @can('tables.view')
                    <flux:sidebar.item icon="squares-plus" href="{{ route('admin.tables.index') }}" :current="request()->routeIs('admin.tables.*')">Plan de salle</flux:sidebar.item>
                @endcan
                @if($uiEstablishment?->reservationsModuleEnabled())
                    @can('reservations.view')
                        <flux:sidebar.item icon="calendar-days" href="{{ route('admin.reservations.index') }}" :current="request()->routeIs('admin.reservations.*')">Réservations</flux:sidebar.item>
                    @endcan
                @endif
                @if($uiEstablishment?->appointmentsModuleEnabled())
                    @can('appointments.view')
                        <flux:sidebar.item icon="clock" href="{{ route('admin.appointments.index') }}" :current="request()->routeIs('admin.appointments.*')">Rendez-vous</flux:sidebar.item>
                    @endcan
                @endif
                @can('sales.view_all')
                    <flux:sidebar.item icon="shopping-bag" href="{{ route('admin.orders.index') }}" :current="request()->routeIs('admin.orders.*')">Commandes</flux:sidebar.item>
                @endcan
                @can('cash_registers.view')
                    <flux:sidebar.item icon="calculator" href="{{ route('admin.cash-sessions.index') }}" :current="request()->routeIs('admin.cash-sessions.*')">Sessions de caisse</flux:sidebar.item>
                @endcan
            </flux:sidebar.group>

            <flux:sidebar.group heading="Finance" expandable :expanded="request()->routeIs('admin.accounting.*', 'admin.reports.*')">
                @can('accounting.view')
                    <flux:sidebar.item icon="building-library" href="{{ route('admin.accounting.chart') }}" :current="request()->routeIs('admin.accounting.chart')">Plan comptable</flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" href="{{ route('admin.accounting.journal') }}" :current="request()->routeIs('admin.accounting.journal')">Journal</flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" href="{{ route('admin.accounting.ledger') }}" :current="request()->routeIs('admin.accounting.ledger')">Grand livre</flux:sidebar.item>
                    <flux:sidebar.item icon="receipt-percent" href="{{ route('admin.accounting.expenses') }}" :current="request()->routeIs('admin.accounting.expenses')">Dépenses</flux:sidebar.item>
                @endcan
                @can('reports.view')
                    <flux:sidebar.item icon="chart-bar" href="{{ route('admin.reports.dashboard') }}" :current="request()->routeIs('admin.reports.*')">Rapports</flux:sidebar.item>
                @endcan
            </flux:sidebar.group>

            @can('users.view')
                <flux:sidebar.item icon="user-group" href="{{ route('admin.users.index') }}" :current="request()->routeIs('admin.users.*')">Utilisateurs</flux:sidebar.item>
            @endcan
            @can('settings.view')
                <flux:sidebar.item icon="cog-6-tooth" href="{{ route('admin.settings.index') }}" :current="request()->routeIs('admin.settings.*')">Paramètres</flux:sidebar.item>
            @endcan
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:dropdown position="top" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials" icon:trailing="chevron-up-down" />
            <flux:menu>
                <flux:menu.item icon="user-circle" href="{{ route('profile.show') }}">Mon profil</flux:menu.item>
                <flux:menu.item icon="device-tablet" href="{{ route('kiosk') }}" target="_blank">Mode kiosque</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Déconnexion</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <flux:header class="yeboox-topbar sticky top-0 z-20 border-b border-zinc-200/80 bg-white/85 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-950/80">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />
        <flux:sidebar.collapse class="hidden lg:flex" tooltip="Rétracter le menu" />
        <div class="ml-2 hidden sm:block">
            <flux:heading>{{ $title ?? 'Yeboox POS' }}</flux:heading>
            <flux:text size="sm">{{ $uiEstablishment?->name ?? 'Administration' }}</flux:text>
        </div>
        <flux:spacer />
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="subtle" icon="sun" aria-label="Changer le thème" tooltip="Changer le thème" />
            <flux:menu>
                <flux:menu.item icon="sun" x-on:click="setTheme('light')">Clair <flux:icon.check x-show="themeMode === 'light'" class="ms-auto size-4" /></flux:menu.item>
                <flux:menu.item icon="moon" x-on:click="setTheme('dark')">Sombre <flux:icon.check x-show="themeMode === 'dark'" class="ms-auto size-4" /></flux:menu.item>
                <flux:menu.item icon="computer-desktop" x-on:click="setTheme('system')">Système <flux:icon.check x-show="themeMode === 'system'" class="ms-auto size-4" /></flux:menu.item>
            </flux:menu>
        </flux:dropdown>
        <flux:button class="hidden sm:inline-flex" variant="subtle" icon="computer-desktop" href="{{ route('pos.terminal') }}">Point de vente</flux:button>
        <flux:dropdown position="bottom" align="end">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials" />
            <flux:menu>
                <flux:menu.item icon="user-circle" href="{{ route('profile.show') }}">Mon profil</flux:menu.item>
                @can('settings.view')<flux:menu.item icon="cog-6-tooth" href="{{ route('admin.settings.index') }}">Paramètres</flux:menu.item>@endcan
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}">@csrf<flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Déconnexion</flux:menu.item></form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main class="yeboox-main-surface">
        <div class="yeboox-main-art" aria-hidden="true">
            <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMin slice" role="presentation">
                <defs>
                    <pattern id="yeboox-main-grid" width="48" height="48" patternUnits="userSpaceOnUse">
                        <path d="M48 0H0V48" fill="none" stroke="currentColor" stroke-width="1" />
                        <circle cx="0" cy="0" r="1.6" fill="currentColor" />
                    </pattern>
                    <linearGradient id="yeboox-main-fade" x1="0" y1="0" x2="0.72" y2="1">
                        <stop offset="0" stop-color="white" stop-opacity=".92" />
                        <stop offset=".55" stop-color="white" stop-opacity=".35" />
                        <stop offset="1" stop-color="white" stop-opacity="0" />
                    </linearGradient>
                    <mask id="yeboox-main-mask">
                        <rect width="1440" height="900" fill="url(#yeboox-main-fade)" />
                    </mask>
                </defs>
                <rect width="1440" height="900" fill="url(#yeboox-main-grid)" mask="url(#yeboox-main-mask)" />
                <path d="M1030-90a330 330 0 1 0 330 330A330 330 0 0 0 1030-90Zm0 82a248 248 0 1 1-248 248A248 248 0 0 1 1030-8Z" fill="currentColor" opacity=".12" />
                <path d="M-90 670c190-130 365-137 525-22s325 104 505-33" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="8 14" opacity=".2" />
            </svg>
        </div>
        <div class="yeboox-page">
            @if (session('success'))
                <x-dismissible-alert variant="success" icon="check-circle" class="mb-6" heading="Opération réussie">{{ session('success') }}</x-dismissible-alert>
            @endif
            @if (session('error'))
                <x-dismissible-alert variant="danger" icon="exclamation-triangle" class="mb-6" heading="Une erreur est survenue">{{ session('error') }}</x-dismissible-alert>
            @endif

            {{ $slot }}
        </div>
    </flux:main>

    <flux:toast />
    @livewireScripts
    @fluxScripts
    <script src="/js/offline-store.js" defer></script>
    @stack('scripts')
</body>
</html>
