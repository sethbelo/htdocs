<div class="yb-pos-shell flex h-dvh flex-col overflow-hidden bg-zinc-100 dark:bg-zinc-950">
    <header class="yb-pos-header z-30 flex h-16 shrink-0 items-center gap-1.5 border-b border-zinc-200 bg-white px-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:gap-3 sm:px-5">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5" wire:navigate>
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-sm font-bold text-white shadow-sm shadow-violet-500/30 sm:size-9">Y</span>
            <div class="hidden min-w-0 sm:block">
                <div class="truncate text-sm font-semibold">{{ \App\Models\Setting::get('company_name', 'Yeboox') }} POS</div>
                <div class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                    <span class="size-1.5 rounded-full {{ $register ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $register ? 'Caisse ouverte · '.$register->opened_at->format('H:i') : 'Caisse fermée' }}
                </div>
            </div>
        </a>

        <flux:spacer />

        <div class="flex shrink-0 items-center gap-2 rounded-xl bg-zinc-100 px-3 py-2 text-xs font-bold text-violet-700 dark:bg-zinc-800 dark:text-violet-300" title="Devise de base du point de vente">
            <flux:icon.banknotes class="size-4" /> {{ \App\Support\DisplayCurrency::baseCode() }}
        </div>

        <flux:dropdown position="bottom" align="end" class="max-[380px]:hidden">
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
            <flux:profile :avatar="auth()->user()->profile_photo_url" :name="auth()->user()->name" class="max-md:hidden" />
            <flux:button variant="subtle" icon="user" square class="md:hidden" />
            <flux:menu>
                <flux:menu.item icon="home" href="{{ route('dashboard') }}" wire:navigate>Administration</flux:menu.item>
                <flux:menu.item icon="user" href="{{ route('profile.show') }}">Mon profil</flux:menu.item>
                <flux:menu.separator />
                <flux:modal.trigger name="confirm-logout">
                    <flux:menu.item as="button" type="button" icon="arrow-right-start-on-rectangle" variant="danger">Déconnexion</flux:menu.item>
                </flux:modal.trigger>
            </flux:menu>
        </flux:dropdown>

        <flux:button variant="danger" icon="lock-closed" wire:click="requestCloseRegister" class="max-sm:hidden">Fermer</flux:button>
        <flux:button variant="danger" icon="lock-closed" square wire:click="requestCloseRegister" class="sm:hidden" />
    </header>

    <div class="flex min-h-0 flex-1">
        <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <div class="shrink-0 space-y-3 border-b border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900 sm:p-4">
                <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(17rem,.62fr)]">
                    <flux:input wire:model.live.debounce.250ms="search" icon="magnifying-glass" placeholder="Nom, SKU ou code-barres…" clearable />
                    <form wire:submit="scanProductCode" class="hidden gap-2 md:flex">
                        <flux:input wire:model="scanCode" icon="qr-code" placeholder="Scanner puis Entrée" autocomplete="off" aria-label="Scanner un code-barres ou QR" class="min-w-0 flex-1" />
                        <flux:button type="submit" variant="primary" color="violet" icon="plus">Ajouter</flux:button>
                    </form>
                    <flux:button type="button" wire:click="openScanner" icon="qr-code" class="md:hidden">Scanner un produit</flux:button>
                </div>
                @if($scanFeedback)<div class="flex items-center gap-2 text-xs font-medium {{ $scanFeedbackType === 'success' ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}"><flux:icon :name="$scanFeedbackType === 'success' ? 'check-circle' : 'exclamation-triangle'" class="size-4" />{{ $scanFeedback }}</div>@endif
                <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
                    <button type="button" wire:click="selectCategory('all')" class="shrink-0 rounded-xl border px-3.5 py-2 text-xs font-semibold transition {{ $category === 'all' ? 'border-violet-600 bg-violet-600 text-white shadow-sm shadow-violet-500/20' : 'border-zinc-200 bg-white text-zinc-600 hover:border-violet-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' }}">Tous <span class="ml-1 opacity-70">{{ $productTotal }}</span></button>
                    @foreach($categories as $item)
                        <button type="button" wire:click="selectCategory(@js($item->category))" class="shrink-0 rounded-xl border px-3.5 py-2 text-xs font-semibold transition {{ $category === $item->category ? 'border-violet-600 bg-violet-600 text-white shadow-sm shadow-violet-500/20' : 'border-zinc-200 bg-white text-zinc-600 hover:border-violet-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' }}">{{ $item->category }} <span class="ml-1 opacity-70">{{ $item->total }}</span></button>
                    @endforeach
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-3 pb-24 sm:p-4 lg:pb-4">
                @error('cart')
                    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">{{ $message }}</div>
                @enderror

                @if($products->isEmpty())
                    <div class="grid h-full place-items-center py-16 text-center">
                        <div><span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-200 text-zinc-500 dark:bg-zinc-800"><flux:icon.magnifying-glass class="size-6" /></span><flux:heading class="mt-4">Aucun article trouvé</flux:heading><flux:text class="mt-1">Essayez un autre nom, SKU ou une autre catégorie.</flux:text></div>
                    </div>
                @else
                    <div class="yb-pos-products grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-5">
                        @foreach($products as $product)
                            @php $unavailable = $product->type === 'product' && (float) $product->stock_qty <= 0; @endphp
                            <button type="button" wire:click="addProduct({{ $product->id }})" @disabled($unavailable || !$register) class="yb-pos-product-card group relative flex min-h-40 flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-55 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-violet-700">
                                <div class="relative flex h-24 w-full items-center justify-center overflow-hidden bg-gradient-to-br from-violet-50 via-white to-indigo-50 dark:from-violet-950/40 dark:via-zinc-900 dark:to-indigo-950/30">
                                    @if($product->image_path)
                                        <img src="{{ Storage::url($product->image_path) }}" alt="" class="size-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex size-12 items-center justify-center rounded-2xl bg-white/80 text-violet-600 shadow-sm dark:bg-zinc-800 dark:text-violet-300"><flux:icon :name="$product->type === 'service' ? 'sparkles' : ($product->type === 'combo' ? 'squares-2x2' : 'cube')" class="size-6" /></span>
                                    @endif
                                    <span class="absolute right-2 top-2 rounded-lg bg-white/90 px-2 py-1 text-[10px] font-semibold text-zinc-600 shadow-sm backdrop-blur dark:bg-zinc-900/90 dark:text-zinc-300">{{ $product->category ?: 'Article' }}</span>
                                </div>
                                <div class="flex flex-1 flex-col p-3">
                                    <div class="line-clamp-2 text-sm font-semibold leading-5">{{ $product->name }}</div>
                                    <div class="mt-auto flex items-end justify-between gap-2 pt-3">
                                        <span class="text-sm font-bold text-violet-700 dark:text-violet-300">{{ $this->displayAmount($product->grossUnitPrice()) }}</span>
                                        @if($product->type === 'product')<span class="text-[10px] {{ $unavailable ? 'text-rose-600' : 'text-zinc-400' }}">{{ $unavailable ? 'Épuisé' : number_format((float) $product->stock_qty, 0).' dispo.' }}</span>@endif
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </main>

        <aside class="yb-pos-cart-panel hidden w-[22rem] shrink-0 flex-col border-l border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 min-[1180px]:flex xl:w-[26rem]">
            @include('livewire.partials.pos-cart')
        </aside>
    </div>

    <div class="fixed inset-x-3 bottom-3 z-30 min-[1180px]:hidden">
        <flux:button variant="primary" color="violet" icon="shopping-cart" wire:click="$set('showCart', true)" class="w-full shadow-xl shadow-violet-950/20">
            Panier · {{ number_format($this->cartCount(), 0) }} article(s)
            <flux:spacer />
            {{ $this->displayAmount($this->total()) }}
        </flux:button>
    </div>

    <flux:modal wire:model="showCart" variant="flyout" position="right" class="w-full sm:w-[28rem] min-[1180px]:hidden">
        <div class="flex h-full min-h-[70vh] flex-col">@include('livewire.partials.pos-cart')</div>
    </flux:modal>

    <flux:modal wire:model="showScanner" class="md:w-[34rem]">
        <div class="flex items-start gap-3 border-b border-zinc-200 pb-4 pr-8 dark:border-zinc-700"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon.qr-code class="size-5" /></span><div><flux:heading size="lg">Lecture produit</flux:heading><flux:text size="sm" class="mt-1">Code-barres, QR ou SKU avec un lecteur USB/Bluetooth en mode clavier.</flux:text></div></div>
        <form wire:submit="scanProductCode" class="mt-5 space-y-4">
            <flux:input wire:model="scanCode" label="Code lu" icon="qr-code" placeholder="Scannez maintenant…" autocomplete="off" autofocus />
            <flux:error name="scanCode" />
            <div class="rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><div class="flex items-center gap-2 font-semibold"><flux:icon.bolt class="size-4 text-amber-500" />Ajout instantané</div><p class="mt-1 text-xs">Le produit reconnu est ajouté directement à la commande active. Le stock et le statut actif sont contrôlés.</p></div>
            <div class="flex justify-end gap-2"><flux:button type="button" variant="ghost" wire:click="$set('showScanner', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet" icon="plus">Ajouter au panier</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showTableSelector" class="h-[calc(100dvh-2rem)]! w-[calc(100vw-2rem)]! max-w-none!">
        <div class="flex flex-col gap-4 border-b border-zinc-200 pb-5 pr-8 dark:border-zinc-700 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon.table-cells class="size-5" /></span>
                <div><flux:heading size="xl">Sélection de table</flux:heading><flux:text size="sm" class="mt-1">L’état est actualisé à chaque modification de commande.</flux:text></div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2"><flux:badge color="emerald" icon="check-circle">{{ $this->availableTablesCount() }} libre(s)</flux:badge><flux:badge color="amber" icon="receipt-percent">{{ $this->occupiedTablesCount() }} occupée(s)</flux:badge></div>
        </div>
        <div class="mt-5 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
            <button type="button" wire:click="$set('selectedTableZone', 'all')" class="shrink-0 rounded-xl px-4 py-2 text-sm font-semibold {{ $selectedTableZone === 'all' ? 'bg-violet-600 text-white' : 'border border-zinc-200 bg-white text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">Toutes les zones</button>
            @foreach($tableZones as $zone)
                <button type="button" wire:click="$set('selectedTableZone', @js($zone['name']))" class="shrink-0 rounded-xl px-4 py-2 text-sm font-semibold {{ $selectedTableZone === $zone['name'] ? 'bg-violet-600 text-white' : 'border border-zinc-200 bg-white text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}"><span class="mr-2 inline-block size-2 rounded-full bg-emerald-500"></span>{{ $zone['name'] }}</button>
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap gap-4 text-xs font-medium text-zinc-500"><span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span>Table libre</span><span class="inline-flex items-center gap-1.5"><span class="grid size-5 place-items-center rounded-md bg-amber-500 text-white"><flux:icon.receipt-percent class="size-3" /></span>Table occupée</span><span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-violet-500"></span>Table sélectionnée</span></div>
        <div class="mt-5 max-h-[calc(100dvh-15rem)] overflow-y-auto rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/50 sm:p-4">
            @foreach($tableZones as $zone)
                @continue($selectedTableZone !== 'all' && $selectedTableZone !== $zone['name'])
                <div class="mb-5 last:mb-0">
                    <div class="mb-3 text-xs font-bold uppercase tracking-wider text-zinc-500">{{ $zone['name'] }}</div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        @foreach($zone['tables'] as $table)
                            @php $hasOrder = $this->tableHasOrder($table['name']); $selected = $tableName === $table['name']; @endphp
                            <button type="button" wire:click="selectTable(@js($table['name']))" class="relative flex min-h-32 flex-col items-center justify-center rounded-2xl border-2 p-3 text-center transition hover:-translate-y-0.5 hover:shadow-md {{ $selected ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-500/15 dark:bg-violet-500/10' : ($hasOrder ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/10' : 'border-zinc-200 bg-white hover:border-emerald-400 dark:border-zinc-700 dark:bg-zinc-900') }}">
                                @if($hasOrder)<span class="absolute right-2 top-2 grid size-6 place-items-center rounded-lg bg-amber-500 text-white"><flux:icon.receipt-percent class="size-3.5" /></span>@endif
                                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Table</span><strong class="mt-1 text-xl">{{ str($table['name'])->after('Table ') }}</strong><span class="mt-1 inline-flex items-center gap-1 text-[11px] text-zinc-500"><flux:icon.user-group class="size-3.5" />{{ $table['capacity'] }} pers.</span><span class="mt-2 rounded-lg px-2 py-1 text-[10px] font-bold uppercase {{ $hasOrder ? 'bg-amber-500 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' }}">{{ $hasOrder ? 'Occupée' : 'Libre' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </flux:modal>

    <flux:modal wire:model="showOpenRegister" :dismissible="false" class="h-[calc(100dvh-2rem)]! w-[calc(100vw-2rem)]! max-w-[90rem]! overflow-y-auto!">
        <div class="relative -mx-6 -mt-6 overflow-hidden rounded-t-2xl bg-linear-to-br from-emerald-600 to-teal-700 p-6 text-white">
            <svg aria-hidden="true" class="absolute -right-8 -top-10 size-48 opacity-15" viewBox="0 0 160 160" fill="none"><circle cx="80" cy="80" r="62" stroke="currentColor" stroke-width="3" stroke-dasharray="8 10"/><circle cx="80" cy="80" r="38" stroke="currentColor" stroke-width="12"/></svg>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center"><span class="grid h-16 w-40 shrink-0 place-items-center rounded-2xl bg-white px-4 shadow-lg"><img src="{{ asset('images/yeboox-logo.png') }}" alt="Yeboox" class="h-auto max-h-11 w-full object-contain" /></span><span class="hidden size-14 shrink-0 place-items-center rounded-2xl bg-white/15 shadow-inner sm:grid"><flux:icon.lock-open class="size-7" /></span><div><div class="text-xs font-bold uppercase tracking-widest text-white/70">Début de service · Session sécurisée</div><flux:heading size="xl" class="mt-1 text-white!">Ouverture de caisse</flux:heading><div class="mt-1 flex items-center gap-2 text-sm text-white/80"><flux:icon.user-circle class="size-4" />{{ auth()->user()->name }} · {{ now()->format('d/m/Y H:i') }}</div></div></div>
        </div>
        <form wire:submit="openRegister" class="mx-auto mt-8 grid max-w-6xl gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-5">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center dark:border-emerald-900 dark:bg-emerald-500/10"><div class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Fonds de caisse initial</div><div class="mt-2 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ $this->displayAmount((float) $openingBalance) }}</div></div>
                <flux:input.group><flux:input type="text" inputmode="decimal" wire:model.live="openingBalance" label="Fond initial de caisse" autofocus /><flux:input.group.suffix>{{ \App\Support\DisplayCurrency::baseCode() }}</flux:input.group.suffix></flux:input.group>
                <flux:textarea wire:model="openingNote" label="Note d’ouverture" badge="Facultatif" placeholder="Ex. fonds remis par le responsable" />
            </div>
            <div class="space-y-4 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/60"><div class="flex items-center gap-2 text-sm font-semibold"><flux:icon.shield-check class="size-5 text-emerald-600" />Session mono-devise</div><flux:text size="sm">La caisse, les paiements et la comptabilité utilisent uniquement {{ \App\Support\DisplayCurrency::baseCode() }}. La seconde monnaie est réservée au ticket client.</flux:text><div class="space-y-2 border-t border-zinc-200 pt-4 text-xs dark:border-zinc-700"><div class="flex justify-between"><span>Taux indicatif</span><strong>{{ \App\Support\DisplayCurrency::rateLabel() }}</strong></div><div class="flex justify-between"><span>État Electron</span><strong class="text-emerald-600">SQLite local</strong></div></div></div>
            <div class="flex justify-end gap-2 md:col-span-2"><flux:button href="{{ route('dashboard') }}" variant="ghost">Retour</flux:button><flux:button type="submit" variant="primary" color="emerald" icon="play">Ouvrir la caisse</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showCloseRegister" :dismissible="false" class="h-[calc(100dvh-2rem)]! w-[calc(100vw-2rem)]! max-w-[90rem]! overflow-y-auto!">
        <div class="relative -mx-6 -mt-6 overflow-hidden rounded-t-2xl bg-linear-to-br from-rose-600 to-red-700 p-6 text-white"><svg aria-hidden="true" class="absolute -right-8 -top-10 size-48 opacity-15" viewBox="0 0 160 160" fill="none"><circle cx="80" cy="80" r="62" stroke="currentColor" stroke-width="3" stroke-dasharray="8 10"/><path d="M50 80h60M80 50v60" stroke="currentColor" stroke-width="12" stroke-linecap="round"/></svg><div class="relative flex flex-col gap-5 sm:flex-row sm:items-center"><span class="grid h-16 w-40 shrink-0 place-items-center rounded-2xl bg-white px-4 shadow-lg"><img src="{{ asset('images/yeboox-logo.png') }}" alt="Yeboox" class="h-auto max-h-11 w-full object-contain" /></span><span class="hidden size-14 shrink-0 place-items-center rounded-2xl bg-white/15 sm:grid"><flux:icon.lock-closed class="size-7" /></span><div><div class="text-xs font-bold uppercase tracking-widest text-white/70">Fin de service · Contrôle obligatoire</div><flux:heading size="xl" class="mt-1 text-white!">Clôture de caisse</flux:heading><div class="mt-1 text-sm text-white/80">Ouverte {{ $register?->opened_at?->diffForHumans() }} · {{ auth()->user()->name }}</div></div></div></div>
        <form wire:submit="closeRegister" class="mx-auto mt-8 grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)]">
            <div class="space-y-3"><div class="text-xs font-bold uppercase tracking-wider text-zinc-500">Résumé de caisse · {{ \App\Support\DisplayCurrency::baseCode() }}</div><div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 text-sm dark:border-violet-900 dark:bg-violet-500/10"><div class="mb-4 flex items-center justify-between"><strong>Devise comptable</strong><flux:badge color="violet">{{ \App\Support\DisplayCurrency::baseCode() }}</flux:badge></div><div class="flex justify-between"><span>Fond initial</span><strong>{{ $this->displayAmount((float) ($register?->opening_balance ?? 0)) }}</strong></div><div class="mt-2 flex justify-between text-emerald-700 dark:text-emerald-300"><span>Espèces encaissées</span><strong>+ {{ $this->displayAmount($this->sessionCashSales()) }}</strong></div><div class="mt-3 flex justify-between border-t border-violet-200 pt-3 text-base dark:border-violet-900"><span class="font-semibold">Solde attendu</span><strong>{{ $this->displayAmount($this->expectedClosingBalance()) }}</strong></div></div><div class="rounded-xl bg-zinc-50 p-3 text-sm dark:bg-zinc-800"><div class="flex justify-between"><span>Paiements non espèces</span><strong>{{ $this->displayAmount($this->sessionNonCashSales()) }}</strong></div><div class="mt-2 text-xs text-zinc-500">La devise secondaire n'entre pas dans la clôture comptable.</div></div>@if($this->pendingOrdersCount() > 0)<div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-100"><div class="flex items-center gap-2 font-bold"><flux:icon.exclamation-triangle class="size-5" />{{ $this->pendingOrdersCount() }} commande(s) en cours</div><p class="mt-1 text-xs">Reprenez chaque commande pour l’encaisser ou la vider avant la fermeture sécurisée.</p><div class="mt-3 space-y-2">@foreach($this->pendingOrdersSummary() as $order)<div class="flex items-center justify-between gap-3 rounded-xl bg-white/80 p-3 text-sm dark:bg-zinc-900/70"><div class="min-w-0"><strong class="block truncate">{{ $order['label'] }}</strong><span class="text-xs opacity-75">{{ $order['items'] }} article(s) · {{ $this->displayAmount($order['total']) }}</span></div><flux:button type="button" size="sm" variant="primary" color="amber" icon="arrow-uturn-left" wire:click="resumePendingOrder(@js($order['key']))">Reprendre</flux:button></div>@endforeach</div></div>@else<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200"><div class="flex items-center gap-2 font-semibold"><flux:icon.check-circle class="size-5" />Aucune commande en cours</div><p class="mt-1 text-xs">La caisse peut être clôturée en toute sécurité.</p></div>@endif</div>
            <div class="space-y-4"><div class="text-xs font-bold uppercase tracking-wider text-zinc-500">Comptage physique</div><flux:input.group><flux:input type="text" inputmode="decimal" wire:model.live.debounce.250ms="closingBalance" label="Montant compté" /><flux:input.group.suffix>{{ \App\Support\DisplayCurrency::baseCode() }}</flux:input.group.suffix></flux:input.group><div class="rounded-2xl border-2 p-4 {{ abs($this->closingDifference()) < 0.01 ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-500/10 dark:text-rose-200' }}"><div class="text-xs font-bold uppercase tracking-wider">Écart de caisse</div><strong class="mt-2 block text-xl">{{ $this->displayAmount($this->closingDifference()) }}</strong></div><flux:textarea wire:model="sessionNote" label="Note de clôture" badge="{{ abs($this->closingDifference()) >= 0.01 ? 'Obligatoire' : 'Facultatif' }}" placeholder="Expliquez tout écart constaté" /></div>
            <div class="flex justify-end gap-2 md:col-span-2"><flux:button type="button" variant="ghost" wire:click="$set('showCloseRegister', false)">Continuer les ventes</flux:button><flux:button type="submit" variant="danger" icon="shield-check" :disabled="$this->pendingOrdersCount() > 0">Clôturer la caisse</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showAddition" class="md:w-[52rem]">
        @php
            $legalTopTemplates = ['elegant', 'eco', 'playlist', 'transaction', 'timestamp'];
            $codeValue = \App\Support\DocumentPreferences::resolveCodeValue($receiptPreferences, $additionReference, $company['name']);
        @endphp
        <div class="flex flex-col gap-3 border-b border-zinc-200 pb-4 pr-8 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between"><div><flux:heading size="xl">Addition provisoire</flux:heading><flux:text class="mt-1">Aperçu fidèle au modèle sélectionné dans Configuration · Tickets & PDF.</flux:text></div><div class="flex gap-2"><flux:badge color="violet" icon="receipt-percent">{{ ucfirst($receiptPreferences['template']) }} · {{ $receiptPreferences['paper_width'] }} mm</flux:badge><flux:button type="button" variant="primary" color="violet" icon="printer" x-on:click="window.printPosAddition()">Imprimer</flux:button></div></div>
        <div class="mt-5 max-h-[68vh] overflow-y-auto rounded-2xl bg-zinc-100 p-5 dark:bg-zinc-800">
            <article id="pos-addition-print" data-paper-width="{{ $receiptPreferences['paper_width'] }}" data-receipt-template="{{ $receiptPreferences['template'] }}" class="pos-addition mx-auto bg-white p-5 text-black shadow-sm" style="width:{{ $receiptPreferences['paper_width'] }}mm;max-width:100%;{{ $receiptStyle }}">
                <header class="pos-ticket-header text-center"><div class="text-lg font-black uppercase">{{ $company['name'] }}</div>@if($company['address'])<div class="mt-1 text-[.82em]">{{ $company['address'] }}</div>@endif @if($company['phone'])<div class="text-[.82em]">{{ $company['phone'] }}</div>@endif</header>
                @if(in_array($receiptPreferences['template'], $legalTopTemplates, true) && ($company['rccm'] || $company['nif'] || $company['tva']))<div class="pos-ticket-legal mt-3 border-y border-dashed border-black py-2 text-[.75em]">@if($company['rccm'])<div>RCCM : {{ $company['rccm'] }}</div>@endif @if($company['nif'])<div>IMPÔT/NIF : {{ $company['nif'] }}</div>@endif @if($company['tva'])<div>TVA : {{ $company['tva'] }}</div>@endif</div>@endif
                <div class="my-3 border-t border-dashed border-black/60"></div><div class="text-center text-sm font-black uppercase tracking-wider">Addition · Non encaissée</div>
                <div class="pos-ticket-meta mt-3 space-y-1 text-[.82em]"><div class="flex justify-between"><span>Référence</span><strong>{{ $additionReference }}</strong></div><div class="flex justify-between"><span>Date</span><span>{{ now()->format('d/m/Y') }}</span></div><div class="flex justify-between"><span>Heure</span><span>{{ now()->format('H:i:s') }}</span></div><div class="flex justify-between"><span>Caissier</span><span>{{ auth()->user()->name }}</span></div>@if($tableName)<div class="flex justify-between"><span>Table</span><strong>{{ $tableName }}</strong></div>@endif @if($selectedCustomer)<div class="flex justify-between"><span>Client</span><span>{{ $selectedCustomer->name }}</span></div>@endif</div>
                <div class="pos-ticket-items my-3 border-y border-dashed border-black py-2 text-[.88em]">@foreach($cart as $line)<div class="mb-1.5 flex items-start justify-between gap-3"><span><strong>{{ number_format((float) $line['quantity'], 0) }}×</strong> {{ $line['name'] }}</span><span class="whitespace-nowrap">{{ $this->displayAmount($this->cartLineGrossTotal($line)) }}</span></div>@endforeach</div>
                <div class="space-y-1 text-[.86em]"><div class="flex justify-between"><span>Sous-total</span><span>{{ $this->displayAmount($this->subtotal()) }}</span></div><div class="flex justify-between"><span>Taxes</span><span>{{ $this->displayAmount($this->taxTotal()) }}</span></div>@if($discountAmount > 0)<div class="flex justify-between"><span>Remise</span><span>- {{ $this->displayAmount((float) $discountAmount) }}</span></div>@endif</div><div class="pos-ticket-total mt-3 border-y-2 border-black py-2"><div class="flex justify-between text-[1.2em] font-black"><span>TOTAL</span><span>{{ $this->displayAmount($this->total()) }}</span></div><div class="mt-1 flex justify-between text-[.78em]"><span>Équivalent {{ \App\Support\DisplayCurrency::secondaryCode() }}</span><span>{{ \App\Support\DisplayCurrency::format($this->total(), \App\Support\DisplayCurrency::secondaryCode()) }}</span></div></div><div class="mt-1 text-center text-[.65em]">{{ \App\Support\DisplayCurrency::rateLabel() }}</div>
                @if($receiptPreferences['code_type'] !== 'none')<div class="mt-4 flex justify-center">{!! \App\Support\DocumentCode::render($receiptPreferences['code_type'], $codeValue, 112) !!}</div>@endif
                @if(!in_array($receiptPreferences['template'], $legalTopTemplates, true) && ($company['rccm'] || $company['nif'] || $company['tva']))<div class="pos-ticket-legal mt-4 space-y-0.5 text-center text-[.72em]">@if($company['rccm'])<div>RCCM : {{ $company['rccm'] }}</div>@endif @if($company['nif'])<div>IMPÔT/NIF : {{ $company['nif'] }}</div>@endif @if($company['tva'])<div>TVA : {{ $company['tva'] }}</div>@endif</div>@endif
                <footer class="pos-ticket-footer mt-4 text-center text-[.8em]">{{ $company['footer'] }}</footer><div class="mt-2 text-center text-[.68em] opacity-60">Document provisoire — ne vaut pas reçu de paiement</div>
            </article>
        </div>
    </flux:modal>

    <flux:modal wire:model="showPayment" class="h-[calc(100dvh-2rem)]! w-[calc(100vw-2rem)]! max-w-none!">
        <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 pr-8 dark:border-zinc-700"><div><flux:heading size="xl">Encaissement multiple</flux:heading><flux:text class="mt-1">Combinez espèces, Mobile Money, banque, YePay et points du client.</flux:text></div><div class="text-right"><div class="text-xs text-zinc-500">À payer</div><div class="text-2xl font-bold text-violet-700 dark:text-violet-300">{{ $this->displayAmount($this->total()) }}</div></div></div>
        <form wire:submit="checkout" class="mt-5 grid min-h-0 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-4">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                    @foreach([['cash','banknotes','Espèces'],['mobile_money','device-phone-mobile','Mobile'],['bank','building-library','Banque'],['yepay','sparkles','YePay'],['points','star','Points']] as [$code,$icon,$label])
                        @continue($code === 'points' && (!$loyaltyEnabled || $loyaltyMode !== 'points'))
                        <button type="button" wire:click="addPaymentLine('{{ $code }}')" class="flex min-h-20 flex-col items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white p-2 text-xs font-semibold transition hover:border-violet-400 hover:bg-violet-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-violet-500/10"><flux:icon :name="$icon" class="size-5 text-violet-600 dark:text-violet-300" />{{ $label }}</button>
                    @endforeach
                </div>
                @error('paymentLines')<div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-500/10 dark:text-rose-300">{{ $message }}</div>@enderror
                <div class="max-h-[calc(100dvh-17rem)] space-y-3 overflow-y-auto pr-1">
                    @foreach($paymentLines as $index => $line)
                        <div wire:key="payment-line-{{ $index }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex flex-wrap items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon :name="match($line['method']) {'cash'=>'banknotes','mobile_money'=>'device-phone-mobile','bank'=>'building-library','yepay'=>'sparkles','points'=>'star',default=>'credit-card'}" class="size-5" /></span><div class="min-w-0 flex-1"><div class="font-semibold">{{ $this->paymentLabel($line['method']) }}</div>@if($line['provider'])<div class="truncate text-xs text-zinc-500">{{ $line['provider'] }}</div>@elseif($line['method'] === 'points' && $selectedCustomer)<div class="text-xs text-amber-600">{{ number_format($selectedCustomer->points) }} pts · {{ $this->displayAmount($this->customerPointsValue()) }}</div>@endif</div><flux:badge color="violet">{{ \App\Support\DisplayCurrency::baseCode() }}</flux:badge>@if(!in_array($line['method'], ['cash','points']))<flux:button type="button" wire:click="configurePaymentLine({{ $index }})" size="sm" icon="cog-6-tooth">{{ $line['provider'] ? 'Modifier' : 'Configurer' }}</flux:button>@endif<flux:button type="button" wire:click="removePaymentLine({{ $index }})" variant="ghost" icon="x-mark" square /></div>
                            <div class="mt-3 grid gap-3 {{ $line['method'] === 'cash' || in_array($line['method'], ['mobile_money','bank','yepay']) ? 'sm:grid-cols-2' : '' }}">
                                <flux:input type="text" inputmode="decimal" wire:model.live.debounce.450ms="paymentLines.{{ $index }}.amount" label="{{ $line['method'] === 'cash' ? 'Part encaissée' : 'Montant' }}" suffix="{{ \App\Support\DisplayCurrency::baseCode() }}" autocomplete="off" placeholder="0,00" />
                                @if($line['method'] === 'cash')
                                    <flux:input type="text" inputmode="decimal" wire:model.live.debounce.450ms="paymentLines.{{ $index }}.received" label="Montant reçu" icon="banknotes" suffix="{{ \App\Support\DisplayCurrency::baseCode() }}" autocomplete="off" placeholder="0,00" />
                                @elseif(in_array($line['method'], ['mobile_money','bank','yepay']))
                                    <flux:input wire:model="paymentLines.{{ $index }}.reference" label="Référence" icon="hashtag" placeholder="N° transaction" />
                                @endif
                            </div>
                            @error("paymentLines.$index.amount")<div class="mt-2 text-xs text-rose-600">{{ $message }}</div>@enderror @error("paymentLines.$index.received")<div class="mt-2 text-xs text-rose-600">{{ $message }}</div>@enderror @error("paymentLines.$index.provider")<div class="mt-2 text-xs text-rose-600">{{ $message }}</div>@enderror @error("paymentLines.$index.reference")<div class="mt-2 text-xs text-rose-600">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
            <aside class="space-y-4 self-start rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-800/60 lg:sticky lg:top-0"><div><div class="flex items-center justify-between"><div class="text-xs font-bold uppercase tracking-wider text-zinc-500">Résumé de l’encaissement</div><flux:badge color="violet">{{ \App\Support\DisplayCurrency::baseCode() }}</flux:badge></div><div class="mt-4 space-y-3 text-sm"><div class="flex justify-between"><span>Total commande</span><strong>{{ $this->displayAmount($this->total()) }}</strong></div><div class="flex justify-between"><span>Déjà affecté</span><strong>{{ $this->displayAmount($this->paymentLinesTotal()) }}</strong></div><div class="flex justify-between border-t border-zinc-200 pt-4 text-base dark:border-zinc-700"><span class="font-semibold">Reste à payer</span><strong class="text-xl {{ abs($this->paymentRemaining()) < .01 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $this->displayAmount($this->paymentRemaining()) }}</strong></div></div></div>@if($this->changeDue(\App\Support\DisplayCurrency::baseCode()) > 0)<div class="rounded-xl bg-emerald-100 p-4 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-200"><div class="flex items-center gap-2 font-bold"><flux:icon.arrow-path-rounded-square class="size-5" />Monnaie à rendre</div><div class="mt-3 flex justify-between"><span>Rendu</span><strong class="text-xl">{{ $this->displayAmount($this->changeDue(\App\Support\DisplayCurrency::baseCode())) }}</strong></div></div>@endif @if($loyaltyEnabled && $selectedCustomer && $loyaltyMode === 'points')<div class="rounded-xl bg-amber-100 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"><div class="flex items-center gap-2 font-semibold"><flux:icon.wallet class="size-4" />Wallet fidélité</div><div class="mt-3 flex justify-between"><span>{{ number_format($selectedCustomer->points) }} pts</span><strong>{{ $this->displayAmount($this->customerPointsValue()) }}</strong></div></div>@elseif($loyaltyEnabled && $selectedCustomer && $loyaltyMode === 'services')<div class="rounded-xl bg-emerald-100 p-4 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200"><div class="flex items-center gap-2 font-semibold"><flux:icon.gift class="size-4" />Carte prestations</div><div class="mt-3 flex justify-between"><span>{{ $selectedCustomer->loyalty_service_count }}/{{ $loyaltyRequiredServices }} prestations</span><strong>Carte cadeau : {{ $selectedCustomer->loyalty_reward_balance > 0 ? '1 offerte' : '0' }}</strong></div></div>@elseif($loyaltyEnabled)<div class="rounded-xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700">Sélectionnez un client pour utiliser sa fidélité.</div>@endif<div class="rounded-xl bg-white p-4 text-xs text-zinc-500 shadow-sm dark:bg-zinc-900"><flux:icon.shield-check class="mb-2 size-5 text-emerald-600" />Tous les paiements sont enregistrés dans la devise comptable unique. L’équivalent secondaire est imprimé sur le ticket.</div></aside>
            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:col-span-2"><flux:button type="button" variant="ghost" wire:click="$set('showPayment', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet" icon="check-circle" :disabled="abs($this->paymentRemaining()) > .01">Confirmer l’encaissement</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showPaymentProvider" class="md:w-[36rem]">
        @php $editingMethod = $editingPaymentIndex !== null ? ($paymentLines[$editingPaymentIndex]['method'] ?? '') : ''; @endphp
        <div class="flex items-center gap-3 border-b border-zinc-200 pb-4 pr-8 dark:border-zinc-700"><span class="grid size-11 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon :name="$editingMethod === 'bank' ? 'building-library' : ($editingMethod === 'mobile_money' ? 'device-phone-mobile' : 'sparkles')" class="size-5" /></span><div><flux:heading size="lg">{{ $editingMethod === 'bank' ? 'Choisir une banque' : ($editingMethod === 'mobile_money' ? 'Choisir un opérateur Mobile Money' : 'Choisir le service YePay') }}</flux:heading><flux:text size="sm" class="mt-1">La liste vient des paramètres du point de vente.</flux:text></div></div>
        <flux:input wire:model.live.debounce.200ms="providerSearch" icon="magnifying-glass" placeholder="Rechercher…" class="mt-5" />
        <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">@forelse(collect($this->providersFor($editingMethod))->filter(fn($provider) => $providerSearch === '' || str_contains(mb_strtolower($provider['name']), mb_strtolower($providerSearch))) as $provider)<button type="button" wire:click="selectPaymentProvider(@js($provider['name']))" class="flex w-full items-center gap-3 rounded-xl border border-zinc-200 p-3 text-left transition hover:border-violet-400 hover:bg-violet-50 dark:border-zinc-700 dark:hover:bg-violet-500/10"><span class="grid size-10 place-items-center rounded-xl bg-zinc-100 text-violet-600 dark:bg-zinc-800"><flux:icon :name="$provider['icon']" class="size-5" /></span><span class="flex-1 font-semibold">{{ $provider['name'] }}</span><flux:icon.chevron-right class="size-4 text-zinc-400" /></button>@empty<div class="py-10 text-center text-sm text-zinc-500">Aucun établissement trouvé.</div>@endforelse</div>
    </flux:modal>

    <flux:modal wire:model="showReceipt" class="md:w-[52rem]">
        @php
            $receiptReference = $lastSale ? 'POS-'.now()->format('Ymd').'-'.str_pad((string) $lastSale->id, 5, '0', STR_PAD_LEFT) : 'POS';
            $receiptCodeValue = \App\Support\DocumentPreferences::resolveCodeValue($receiptPreferences, $receiptReference, $company['name']);
            $legalTopTemplates = ['elegant', 'eco', 'playlist', 'transaction', 'timestamp'];
        @endphp
        <div class="flex flex-col gap-3 border-b border-zinc-200 pb-4 pr-8 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-3"><span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.check class="size-6" /></span><div><flux:heading size="xl">Paiement enregistré</flux:heading><flux:text class="mt-1">Vente #{{ $lastSaleId }} · ticket prêt à imprimer.</flux:text></div></div><flux:button type="button" variant="primary" color="violet" icon="printer" x-on:click="window.printPosReceipt()">Imprimer le ticket</flux:button></div>
        @if($lastSale)
            <div class="mt-5 max-h-[68vh] overflow-y-auto rounded-2xl bg-zinc-100 p-5 dark:bg-zinc-800">
                <article id="pos-receipt-print" data-paper-width="{{ $receiptPreferences['paper_width'] }}" data-receipt-template="{{ $receiptPreferences['template'] }}" class="pos-addition mx-auto bg-white p-5 text-black shadow-sm" style="width:{{ $receiptPreferences['paper_width'] }}mm;max-width:100%;{{ $receiptStyle }}">
                    <header class="pos-ticket-header text-center"><div class="text-lg font-black uppercase">{{ $company['name'] }}</div>@if($company['address'])<div class="mt-1 text-[.82em]">{{ $company['address'] }}</div>@endif @if($company['phone'])<div class="text-[.82em]">{{ $company['phone'] }}</div>@endif</header>
                    @if(in_array($receiptPreferences['template'], $legalTopTemplates, true) && ($company['rccm'] || $company['nif'] || $company['tva']))<div class="pos-ticket-legal mt-3 border-y border-dashed border-black py-2 text-[.75em]">@if($company['rccm'])<div>RCCM : {{ $company['rccm'] }}</div>@endif @if($company['nif'])<div>IMPÔT/NIF : {{ $company['nif'] }}</div>@endif @if($company['tva'])<div>TVA : {{ $company['tva'] }}</div>@endif</div>@endif
                    <div class="my-3 border-t border-dashed border-black/60"></div><div class="text-center text-sm font-black uppercase tracking-wider">Ticket de caisse</div>
                    <div class="pos-ticket-meta mt-3 space-y-1 text-[.82em]"><div class="flex justify-between"><span>Référence</span><strong>{{ $receiptReference }}</strong></div><div class="flex justify-between"><span>Date</span><span>{{ $lastSale->created_at->format('d/m/Y') }}</span></div><div class="flex justify-between"><span>Heure</span><span>{{ $lastSale->created_at->format('H:i:s') }}</span></div><div class="flex justify-between"><span>Caissier</span><span>{{ $lastSale->user?->name }}</span></div>@if($lastSale->table_name)<div class="flex justify-between"><span>Table</span><strong>{{ $lastSale->table_name }}</strong></div>@endif @if($lastSale->customer)<div class="flex justify-between"><span>Client</span><span>{{ $lastSale->customer->name }}</span></div>@endif</div>
                    <div class="pos-ticket-items my-3 border-y border-dashed border-black py-2 text-[.88em]">@foreach($lastSale->items as $item)<div class="mb-1.5 flex items-start justify-between gap-3"><span><strong>{{ number_format((float) $item->quantity, 0) }}×</strong> {{ $item->product?->name ?? 'Article' }}</span><span class="whitespace-nowrap">{{ $this->displayAmount((float) $item->total_price + (float) $item->tax_amount) }}</span></div>@endforeach</div>
                    <div class="space-y-1 text-[.86em]"><div class="flex justify-between"><span>Sous-total</span><span>{{ $this->displayAmount((float) $lastSale->total_amount - (float) $lastSale->tax_amount + (float) $lastSale->discount_amount) }}</span></div><div class="flex justify-between"><span>Taxes</span><span>{{ $this->displayAmount((float) $lastSale->tax_amount) }}</span></div>@if((float) $lastSale->discount_amount > 0)<div class="flex justify-between"><span>Remise</span><span>- {{ $this->displayAmount((float) $lastSale->discount_amount) }}</span></div>@endif</div>
                    <div class="pos-ticket-total mt-3 border-y-2 border-black py-2"><div class="flex justify-between text-[1.2em] font-black"><span>TOTAL</span><span>{{ $this->displayAmount((float) $lastSale->total_amount) }}</span></div><div class="mt-1 flex justify-between text-[.78em]"><span>Équivalent {{ \App\Support\DisplayCurrency::secondaryCode() }}</span><span>{{ \App\Support\DisplayCurrency::format((float) $lastSale->total_amount, \App\Support\DisplayCurrency::secondaryCode()) }}</span></div></div><div class="mt-1 text-center text-[.65em]">{{ \App\Support\DisplayCurrency::rateLabel() }}</div>
                    <div class="my-3 border-t border-dashed border-black/60"></div><div class="space-y-1 text-[.82em]">@foreach($lastSale->payments as $payment)<div class="flex justify-between"><span>{{ $this->paymentLabel($payment->method) }}@if($payment->provider) · {{ $payment->provider }}@endif</span><span>{{ $this->formatCurrencyAmount((float) $payment->amount, $payment->currency) }}</span></div>@if($payment->method === 'cash')<div class="flex justify-between"><span>Montant reçu</span><strong>{{ $this->formatCurrencyAmount((float) data_get($payment->metadata, 'received', $payment->amount), $payment->currency) }}</strong></div>@endif @endforeach</div>
                    @if(($lastChangeByCurrency[\App\Support\DisplayCurrency::baseCode()] ?? 0) > 0)<div class="mt-3 border-y-2 border-black py-2 text-[1.08em] font-black"><div>MONNAIE RENDUE</div><div class="mt-1 flex justify-between"><span>{{ \App\Support\DisplayCurrency::baseCode() }}</span><span>{{ $this->displayAmount($lastChangeByCurrency[\App\Support\DisplayCurrency::baseCode()]) }}</span></div></div>@endif
                    @if($receiptPreferences['code_type'] !== 'none')<div class="mt-4 flex justify-center">{!! \App\Support\DocumentCode::render($receiptPreferences['code_type'], $receiptCodeValue, 112) !!}</div>@endif
                    @if(!in_array($receiptPreferences['template'], $legalTopTemplates, true) && ($company['rccm'] || $company['nif'] || $company['tva']))<div class="pos-ticket-legal mt-4 space-y-0.5 text-center text-[.72em]">@if($company['rccm'])<div>RCCM : {{ $company['rccm'] }}</div>@endif @if($company['nif'])<div>IMPÔT/NIF : {{ $company['nif'] }}</div>@endif @if($company['tva'])<div>TVA : {{ $company['tva'] }}</div>@endif</div>@endif
                    <footer class="pos-ticket-footer mt-4 text-center text-[.8em]">{{ $company['footer'] }}</footer>
                </article>
            </div>
        @endif
        <div class="mt-5 flex justify-end"><flux:button variant="primary" color="violet" wire:click="$set('showReceipt', false)">Nouvelle vente</flux:button></div>
    </flux:modal>
</div>
