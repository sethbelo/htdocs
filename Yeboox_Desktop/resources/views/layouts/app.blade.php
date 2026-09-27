<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('company_name', 'Yeboox') }} · Gestion</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
    @php
        $user = Auth::user();
        $companyName = \App\Models\Setting::get('company_name', 'Yeboox');
        $companyInitial = mb_strtoupper(mb_substr(trim((string) $companyName), 0, 1)) ?: 'Y';
        $navItems = [
            ['route' => 'master.companies', 'label' => 'Plateforme SaaS', 'icon' => 'building-office-2', 'show' => $user->is_master_admin],
            ['route' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'home', 'show' => $user->can('view dashboard')],
            ['route' => 'pos', 'label' => 'Point de vente', 'icon' => 'shopping-cart', 'show' => $user->can('manage POS')],
            ['route' => 'sales', 'label' => 'Ventes', 'icon' => 'banknotes', 'show' => $user->can('manage sales')],
            ['route' => 'appointments', 'label' => 'Rendez-vous', 'icon' => 'calendar-days', 'show' => $user->can('manage appointments')],
            ['route' => 'customers', 'label' => 'Clients', 'icon' => 'users', 'show' => $user->can('manage customers')],
            ['route' => 'inventory', 'label' => 'Inventaire', 'icon' => 'cube', 'show' => $user->can('manage inventory')],
            ['route' => 'invoices', 'label' => 'Facturation', 'icon' => 'document-text', 'show' => $user->can('manage invoices')],
            ['route' => 'accounting', 'label' => 'Comptabilité', 'icon' => 'building-library', 'show' => $user->can('manage accounting')],
            ['route' => 'expenses', 'label' => 'Dépenses', 'icon' => 'receipt-percent', 'show' => $user->can('manage operations') && \App\Support\ModuleRegistry::enabled('expenses')],
            ['route' => 'purchases', 'label' => 'Achats', 'icon' => 'shopping-bag', 'show' => $user->can('manage operations') && \App\Support\ModuleRegistry::enabled('purchases')],
            ['route' => 'disbursements', 'label' => 'Décaissements', 'icon' => 'hand-raised', 'show' => $user->can('manage operations') && \App\Support\ModuleRegistry::enabled('disbursements')],
            ['route' => 'equity', 'label' => 'Capitaux propres', 'icon' => 'building-library', 'show' => $user->can('manage operations') && \App\Support\ModuleRegistry::enabled('equity')],
            ['route' => 'planning', 'label' => 'Planning mensuel', 'icon' => 'calendar-days', 'show' => $user->can('manage operations') && \App\Support\ModuleRegistry::enabled('planning')],
            ['route' => 'reports', 'label' => 'Rapports', 'icon' => 'chart-bar-square', 'show' => $user->can('manage reports')],
            ['route' => 'admin', 'label' => 'Configuration', 'icon' => 'cog-6-tooth', 'show' => $user->can('manage administration')],
        ];
    @endphp

    <flux:accent color="violet" />

    <flux:sidebar sticky collapsible class="yb-sidebar-art no-print border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="{{ route('dashboard') }}"
                name="{{ $companyName }}"
                aria-label="Accueil de {{ $companyName }}"
                x-data="{ initial: @js($companyInitial) }"
                x-on:company-identity-updated.window="initial = $event.detail.initial"
            >
                <x-slot:logo class="rounded-lg!">
                    <span
                        class="grid size-6 place-items-center rounded-lg bg-violet-700 text-xs font-black text-white shadow-sm ring-1 ring-violet-800/20 dark:bg-violet-300 dark:text-violet-950 dark:ring-white/20"
                        aria-hidden="true"
                        x-text="initial"
                    >{{ $companyInitial }}</span>
                </x-slot:logo>
            </flux:sidebar.brand>
            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            @foreach($navItems as $item)
                @continue(!$item['show'])
                <flux:sidebar.item
                    :icon="$item['icon']"
                    href="{{ route($item['route']) }}"
                    :current="request()->routeIs($item['route'])"
                    wire:navigate
                >{{ $item['label'] }}</flux:sidebar.item>
            @endforeach
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:dropdown position="top" align="start">
                <flux:sidebar.item icon="swatch" icon:trailing="chevron-up-down">Apparence</flux:sidebar.item>
                <flux:menu>
                    <flux:menu.radio.group x-data x-model="$flux.appearance">
                        <flux:menu.radio value="light" icon="sun">Clair</flux:menu.radio>
                        <flux:menu.radio value="dark" icon="moon">Sombre</flux:menu.radio>
                        <flux:menu.radio value="system" icon="computer-desktop">Système</flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar.nav>

        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:sidebar.profile :avatar="$user->profile_photo_url" :name="$user->name" />
            <flux:menu>
                <flux:menu.item icon="user" href="{{ route('profile.show') }}">Mon profil</flux:menu.item>
                <flux:menu.separator />
                <flux:modal.trigger name="confirm-logout">
                    <flux:menu.item as="button" type="button" icon="arrow-right-start-on-rectangle" variant="danger">Déconnexion</flux:menu.item>
                </flux:modal.trigger>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    @php
        $activeNav = collect($navItems)->first(fn ($item) => request()->routeIs($item['route']));
    @endphp

    <flux:header sticky class="no-print gap-2 border-b border-zinc-200/80 bg-white/90 px-2! backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950/90 sm:gap-3 sm:px-4!">
        <flux:sidebar.toggle icon="bars-2" inset="left" class="lg:hidden" />
        <div class="min-w-0 max-w-[34vw] sm:max-w-none">
            <flux:heading size="lg" class="truncate">{{ $activeNav['label'] ?? 'Yeboox' }}</flux:heading>
            <flux:text size="sm" class="hidden sm:block">{{ now()->translatedFormat('l d F Y') }}</flux:text>
        </div>
        <flux:spacer />

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
            <flux:badge color="emerald" icon="check-circle" class="max-xl:hidden">Système opérationnel</flux:badge>

            @php
                $availablePoints = $user->is_master_admin
                    ? \App\Models\PointOfSale::query()->with('company:id,name')->where('is_active', true)->orderBy('name')->get()
                    : ($user->is_company_admin
                        ? $user->company?->pointsOfSale()->with('company:id,name')->where('is_active', true)->orderBy('name')->get() ?? collect()
                        : $user->pointsOfSale()->with('company:id,name')->where('is_active', true)->orderBy('name')->get());
                $activePoint = $availablePoints->firstWhere('id', $user->current_point_of_sale_id);
            @endphp
            @if($availablePoints->count() > 1)
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="subtle" icon="building-storefront" class="max-md:hidden">
                        {{ $activePoint?->name ?? 'Choisir un PDV' }}
                    </flux:button>
                    <flux:button variant="subtle" icon="building-storefront" square class="md:hidden" tooltip="Changer de point de vente" />
                    <flux:menu>
                        <flux:menu.heading>Point de vente actif</flux:menu.heading>
                        @foreach($availablePoints as $point)
                            <form method="POST" action="{{ route('preferences.point-of-sale') }}">
                                @csrf
                                <input type="hidden" name="point_of_sale_id" value="{{ $point->id }}">
                                <flux:menu.item as="button" type="submit" icon="building-storefront">
                                    {{ $point->name }}
                                    @if($user->is_master_admin)<span class="block text-xs opacity-60">{{ $point->company?->name }}</span>@endif
                                </flux:menu.item>
                            </form>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif

            <x-currency-switcher />

            <flux:dropdown position="bottom" align="end">
                <flux:button variant="subtle" icon="swatch" square tooltip="Apparence" />
                <flux:menu>
                    <flux:menu.radio.group x-data x-model="$flux.appearance">
                        <flux:menu.radio value="light" icon="sun">Clair</flux:menu.radio>
                        <flux:menu.radio value="dark" icon="moon">Sombre</flux:menu.radio>
                        <flux:menu.radio value="system" icon="computer-desktop">Système</flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>

            <flux:dropdown position="bottom" align="end">
                <flux:profile :avatar="$user->profile_photo_url" :name="$user->name" class="max-sm:hidden" />
                <flux:button variant="subtle" icon="user" square class="sm:hidden" />
                <flux:menu>
                    <flux:menu.item icon="identification" disabled>{{ $user->getRoleNames()->first() }}</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="user" href="{{ route('profile.show') }}">Mon profil</flux:menu.item>
                    <flux:menu.separator />
                    <flux:modal.trigger name="confirm-logout">
                        <flux:menu.item as="button" type="button" icon="arrow-right-start-on-rectangle" variant="danger">Déconnexion</flux:menu.item>
                    </flux:modal.trigger>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    <flux:main class="yb-app-surface max-w-none! overflow-x-clip p-3! sm:p-5! lg:p-8!">
        @if(isset($header))
            <div class="mb-6">{{ $header }}</div>
        @endif
        {{ $slot }}
    </flux:main>

    <x-logout-confirmation />
    @stack('modals')
    @livewireScripts
    @fluxScripts
    <x-display-runtime />
    <script>
        window.printA4Report = async ({ landscape = false, silent = false } = {}) => {
            if (window.yebooxDesktop?.printA4) {
                const result = await window.yebooxDesktop.printA4({ landscape, silent });
                if (!result?.success && !result?.canceled) {
                    console.error('Impression A4 impossible:', result?.reason ?? 'Erreur inconnue');
                }
                return result;
            }

            window.print();
            return { success: true, fallback: true };
        };
    </script>
</body>
</html>
