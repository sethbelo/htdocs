<div class="flex h-full flex-col"
    x-data="Object.assign(scanner((code) => $wire.scan(code)), {
        full: !! document.fullscreenElement,
        now: new Date(),
        enterFull() { if (! document.fullscreenElement) { document.documentElement.requestFullscreen?.().catch(() => {}) } },
        toggleFull() { document.fullscreenElement ? document.exitFullscreen?.() : this.enterFull() },
    })"
    x-init="setInterval(() => now = new Date(), 1000); $nextTick(() => $flux.modal('pos-startup').show())"
    x-on:fullscreenchange.document="full = !! document.fullscreenElement"
    @keydown.window="onKey($event)">
    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 bg-white px-3 py-2 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
            <x-app-logo class="size-8" />
            <div class="leading-tight">
                <flux:heading>Point de vente</flux:heading>
                <div class="text-xs text-zinc-500" x-text="now.toLocaleString('fr-FR', { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })"></div>
            </div>
            @if ($this->session?->register->warehouse)
                <flux:badge size="sm" color="sky" icon="archive-box" class="max-sm:hidden">Stock : {{ $this->session->register->warehouse->name }}</flux:badge>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @include('livewire.cash.partials.session-bar')
            @if ($this->session)
                @can('inventory.view')<flux:button size="xs" icon="archive-box" wire:click="openStock">Stock & réassort</flux:button>@endcan
            @endif
            <flux:button size="xs" x-on:click="toggleFull()" icon="arrows-pointing-out" x-show="! full">Plein écran</flux:button>
            <flux:button size="xs" x-on:click="toggleFull()" icon="arrows-pointing-in" x-show="full" x-cloak>Quitter le plein écran</flux:button>
            <flux:dropdown align="end">
                <flux:button size="xs" icon="user" icon-trailing="chevron-down">{{ auth()->user()->name }}</flux:button>
                <flux:menu>
                    @if (\App\Support\HomeRoute::name(auth()->user()) !== 'pos')
                        <flux:menu.item icon="home" :href="route('home')">Mon espace</flux:menu.item>
                    @endif
                    @can('cash.collect')<flux:menu.item icon="banknotes" :href="route('cash.checkout')">Caisse commandes</flux:menu.item>@endcan
                    @can('cash.view')<flux:menu.item icon="calculator" :href="route('cash.sessions')">Sessions de caisse</flux:menu.item>@endcan
                    <flux:menu.item icon="finger-print" :href="route('profile.pin')">Changer mon PIN</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}">@csrf<flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Déconnexion</flux:menu.item></form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </header>

    @if (! $this->session)
        <div class="flex flex-1 items-center justify-center p-6">
            <x-empty-state icon="lock-closed" title="Caisse fermée" text="Ouvrez une session pour commencer à vendre.">
                <flux:button variant="primary" icon="lock-open" class="mt-2" x-on:click="$flux.modal('pos-startup').show()">Ouvrir la caisse</flux:button>
            </x-empty-state>
        </div>
    @else
        <div class="grid flex-1 grid-cols-1 overflow-hidden lg:grid-cols-[1fr_420px]">
            {{-- Catalogue --}}
            <section class="flex flex-col overflow-hidden">
                <div class="space-y-3 p-4">
                    <form wire:submit="submitSearch">
                        <flux:input wire:model.live.debounce.250ms="search" icon="magnifying-glass" placeholder="Rechercher ou scanner (code-barres, QR, RFID, SKU)…" size="lg" autofocus />
                    </form>
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <flux:button size="sm" :variant="! $categoryId && ! $favoritesOnly ? 'primary' : 'filled'" wire:click="showAll">Tout</flux:button>
                        <flux:button size="sm" icon="star" :variant="$favoritesOnly ? 'primary' : 'filled'" wire:click="toggleFavorites">Favoris</flux:button>
                        @foreach ($this->categories as $category)
                            <flux:button size="sm" :variant="$categoryId === $category->id ? 'primary' : 'filled'" wire:click="$set('categoryId', {{ $category->id }})" wire:key="cat-{{ $category->id }}">{{ $category->name }}</flux:button>
                        @endforeach
                    </div>
                </div>
                <div class="grid flex-1 auto-rows-min grid-cols-3 gap-2 overflow-y-auto px-4 pb-4 sm:grid-cols-4 lg:grid-cols-5">
                    @forelse ($this->products as $product)
                        @php($inStock = (float) $product->stock > 0)
                        <button type="button" wire:click="addProduct({{ $product->id }})" wire:key="p-{{ $product->id }}" title="{{ $product->name }}"
                            @class(['pos-card group', 'pos-card-out' => ! $inStock])>
                            <div class="relative flex h-16 items-center justify-center overflow-hidden rounded-lg bg-brand-50 dark:bg-white/5">
                                @if ($product->image_path)
                                    <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <span class="font-display text-lg font-extrabold tracking-tight text-brand-300 dark:text-brand-500">{{ mb_strtoupper(mb_substr($product->name, 0, 2)) }}</span>
                                @endif
                                @if ($product->is_favorite)<flux:icon.star variant="solid" class="absolute right-1 top-1 size-3.5 text-safran-400" />@endif
                                <span @class(['absolute bottom-1 left-1 rounded-md px-1 text-[10px] font-semibold', 'bg-white/90 text-zinc-600 dark:bg-black/40 dark:text-zinc-200' => $inStock, 'bg-carmin-600 text-white' => ! $inStock])>{{ $inStock ? (float) $product->stock : 'Rupture' }}</span>
                            </div>
                            <div class="mt-1.5 line-clamp-2 min-h-[2rem] text-[12px] font-medium leading-tight">{{ $product->name }}</div>
                            <x-money :amount="$product->sale_price" class="mt-0.5 text-[13px] font-bold text-brand-700 dark:text-cyan2-300" />
                        </button>
                    @empty
                        <div class="col-span-full"><x-empty-state icon="cube" title="Aucun article" /></div>
                    @endforelse
                </div>
            </section>

            {{-- Panier --}}
            <aside class="flex flex-col overflow-hidden border-s border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="space-y-2 border-b border-zinc-200 p-4 dark:border-zinc-800">
                    @if ($this->client)
                        <div class="flex items-center justify-between rounded-lg bg-brand-50 p-2.5 text-sm dark:bg-brand-500/10">
                            <div>
                                <div class="font-medium">{{ $this->client->displayName() }}</div>
                                @if ($this->client->loyaltyAccount)
                                    <div class="text-xs text-zinc-600 dark:text-zinc-300">{{ $this->client->loyaltyAccount->tier?->name }} · {{ $this->client->loyaltyAccount->points_balance }} pts · carte {{ $this->client->loyaltyAccount->card_number }}</div>
                                @endif
                            </div>
                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="$set('clientId', null)" />
                        </div>
                    @else
                        <div class="relative">
                            <flux:input wire:model.live.debounce.250ms="clientSearch" icon="user" size="sm" placeholder="Client (nom, téléphone, carte fidélité)…" />
                            @if ($this->clientResults->isNotEmpty())
                                <div class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                    @foreach ($this->clientResults as $result)
                                        <button type="button" wire:click="selectClient({{ $result->id }})" wire:key="c-{{ $result->id }}" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">{{ $result->displayName() }} <span class="text-zinc-500">· {{ $result->phone }}</span></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <ul class="flex-1 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
                    @forelse ($cart as $productId => $line)
                        <li class="space-y-1.5 p-3" wire:key="cart-{{ $productId }}">
                            <div class="flex justify-between gap-2 text-sm">
                                <span class="font-medium">{{ $line['name'] }}</span>
                                <x-money :amount="$line['price'] * $line['quantity'] - $line['discount']" class="font-semibold" />
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button size="xs" icon="minus" wire:click="decrement({{ $productId }})" />
                                <span class="w-8 text-center text-sm tabular-nums">{{ $line['quantity'] }}</span>
                                <flux:button size="xs" icon="plus" wire:click="increment({{ $productId }})" />
                                <span class="text-xs text-zinc-500">× <x-money :amount="$line['price']" /></span>
                                @can('pos.discount')
                                    <flux:input wire:model.live.debounce.400ms="cart.{{ $productId }}.discount" type="number" step="0.01" size="xs" class="ms-auto w-20" placeholder="Remise" />
                                @endcan
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="remove({{ $productId }})" />
                            </div>
                        </li>
                    @empty
                        <li class="p-8"><x-empty-state icon="shopping-cart" title="Panier vide" text="Touchez un article ou scannez un code." /></li>
                    @endforelse
                </ul>

                <div class="space-y-3 border-t border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model.blur="promotionCode" size="sm" icon="ticket" placeholder="Code promo" />
                        @if ($this->client?->loyaltyAccount?->points_balance)
                            <flux:input wire:model.live.debounce.400ms="redeemPoints" type="number" min="0" :max="$this->client->loyaltyAccount->points_balance" size="sm" icon="gift" placeholder="Points à utiliser" />
                        @endif
                    </div>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500">Articles</dt><dd>{{ $this->totals['items'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">Remises</dt><dd>− <x-money :amount="$this->totals['discount_total']" /></dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">dont TVA</dt><dd><x-money :amount="$this->totals['tax_total']" /></dd></div>
                        <div class="flex justify-between text-2xl font-bold"><dt>Total</dt><dd><x-money :amount="$this->totals['total']" /></dd></div>
                    </dl>
                    <div class="grid grid-cols-3 gap-2">
                        <flux:button wire:click="clearCart" variant="ghost" icon="x-mark" :disabled="$cart === []">Vider</flux:button>
                        <flux:button wire:click="openPayment" variant="primary" icon="banknotes" class="col-span-2 h-12! text-base!" :disabled="$cart === []">Encaisser</flux:button>
                    </div>
                    @if ($lastSaleId)
                        <flux:button size="sm" class="w-full" icon="printer" :href="route('print.sale-receipt', $lastSaleId)" target="_blank">Réimprimer le dernier ticket</flux:button>
                    @endif
                </div>
            </aside>
        </div>

        <flux:modal name="payment" class="w-full max-w-xl">
            <form wire:submit="checkout" class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Paiement</flux:heading>
                    <div class="text-2xl font-bold"><x-money :amount="$this->totals['total']" /></div>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    @php($total = $this->totals['total'])
                    @foreach (array_unique([ceil($total), ceil($total / 5) * 5, ceil($total / 10) * 10, ceil($total / 20) * 20]) as $quick)
                        <flux:button type="button" wire:click="quickCash({{ $quick }})">{{ number_format($quick, 0, ',', ' ') }}</flux:button>
                    @endforeach
                </div>
                @foreach ($payments as $index => $payment)
                    <div class="grid grid-cols-12 gap-2" wire:key="pp-{{ $index }}">
                        <flux:select wire:model.live="payments.{{ $index }}.method" class="col-span-4">
                            @foreach ($methods as $value => $label)
                                @if ($value !== 'credit' || $this->client?->allow_credit)
                                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>
                        <flux:input wire:model.live.debounce.300ms="payments.{{ $index }}.amount" type="number" step="0.01" class="col-span-3" />
                        <flux:input wire:model="payments.{{ $index }}.reference" placeholder="Référence" class="col-span-4" />
                        @if (count($payments) > 1)
                            <flux:button type="button" icon="trash" variant="ghost" wire:click="removePayment({{ $index }})" class="col-span-1" />
                        @endif
                    </div>
                @endforeach
                @php($given = collect($payments)->sum(fn ($p) => (float) $p['amount']))
                <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <flux:button type="button" size="sm" icon="plus" wire:click="addPayment">Paiement mixte</flux:button>
                    <div>
                        @if ($given >= $total)
                            Monnaie : <strong class="text-lg text-emerald-600"><x-money :amount="$given - $total" /></strong>
                        @else
                            Manque : <strong class="text-lg text-red-600"><x-money :amount="$total - $given" /></strong>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Retour</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">Valider la vente</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Modal de démarrage : ouverture ou reprise de session, puis passage en plein écran --}}
    <flux:modal name="pos-startup" class="w-full max-w-3xl" :dismissible="false" :closable="false" :escapable="false">
        <div class="space-y-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="xl">Bonjour {{ auth()->user()->name }}</flux:heading>
                    <flux:text x-text="now.toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', second: '2-digit' })"></flux:text>
                </div>
                <x-app-logo class="size-10" />
            </div>

            @if ($this->session)
                @php($current = $this->session)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-500/10">
                    <div class="flex items-center gap-2 font-medium text-emerald-800 dark:text-emerald-300"><flux:icon.lock-open class="size-5" /> Session {{ $current->number }} en cours</div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div><dt class="text-xs text-zinc-500">Caisse</dt><dd class="font-medium">{{ $current->register->code }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Entrepôt</dt><dd class="font-medium">{{ $current->register->warehouse?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Ouverte à</dt><dd class="font-medium">{{ $current->opened_at->format('H:i') }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Ventes</dt><dd class="font-medium">{{ $current->sales()->count() }} · <x-money :amount="$current->sales()->sum('total')" /></dd></div>
                    </dl>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <flux:button variant="primary" icon="arrows-pointing-out" class="h-12!" x-on:click="enterFull(); $flux.modal('pos-startup').close()">Reprendre en plein écran</flux:button>
                    <flux:button icon="lock-closed" class="h-12!" x-on:click="$flux.modal('pos-startup').close()" wire:click="openClosing">Clôturer la session</flux:button>
                </div>
            @else
                <form wire:submit="openSession" class="space-y-4">
                    <flux:heading>Choisissez votre caisse</flux:heading>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @forelse ($this->registers as $register)
                            @php($busy = (bool) $register->openSession)
                            <label wire:key="reg-{{ $register->id }}" @class([
                                'relative flex cursor-pointer flex-col gap-1 rounded-xl border p-3 text-sm transition',
                                'border-brand-600 bg-brand-50 ring-1 ring-brand-600 dark:bg-brand-500/10' => $registerId === $register->id,
                                'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' => $registerId !== $register->id && ! $busy,
                                'cursor-not-allowed border-zinc-200 opacity-50 dark:border-zinc-700' => $busy,
                            ])>
                                <input type="radio" class="sr-only" wire:model.live="registerId" value="{{ $register->id }}" @disabled($busy)>
                                <span class="flex items-center justify-between font-medium">{{ $register->code }} — {{ $register->name }}
                                    @if ($busy)<flux:badge size="sm" color="amber">Ouverte · {{ $register->openSession->cashier->name }}</flux:badge>@endif
                                </span>
                                <span class="text-xs text-zinc-500">{{ $register->site->name }}</span>
                                <span class="flex items-center gap-1 text-xs"><flux:icon.archive-box class="size-3.5" /> {{ $register->warehouse?->name ?? 'Aucun entrepôt lié' }}
                                    @if ($alerts = $this->registerStockAlerts[$register->id] ?? 0)<flux:badge size="sm" color="red">{{ $alerts }} en stock faible</flux:badge>@endif
                                </span>
                            </label>
                        @empty
                            <flux:callout variant="warning" icon="exclamation-triangle" heading="Aucune caisse n’est rattachée à vos sièges." class="sm:col-span-2" />
                        @endforelse
                    </div>
                    <flux:error name="registerId" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <flux:input wire:model="openingBalance" type="number" step="0.01" label="Fond de caisse (espèces)" icon="banknotes" />
                        <flux:input wire:model="pin" type="password" inputmode="numeric" label="Votre PIN caisse" icon="finger-print" />
                    </div>
                    <flux:button type="submit" variant="primary" icon="arrows-pointing-out" class="h-12! w-full" x-on:click="enterFull()">Ouvrir la caisse en plein écran</flux:button>
                </form>
            @endif

            <div class="flex items-center justify-between border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                @if (\App\Support\HomeRoute::name(auth()->user()) !== 'pos')
                    <flux:link :href="route('home')">Retour à mon espace</flux:link>
                @else
                    <span></span>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<flux:button type="submit" size="sm" variant="ghost" icon="arrow-right-start-on-rectangle">Déconnexion</flux:button></form>
            </div>
        </div>
    </flux:modal>

    {{-- Stock de l’entrepôt lié à la caisse, réassort et réception des transferts --}}
    @if ($this->session)
        <flux:modal name="pos-stock" class="w-full max-w-[min(960px,96vw)]">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Stock — {{ $this->session->register->warehouse?->name }}</flux:heading>
                    <flux:text>Chaque vente de la caisse {{ $this->session->register->code }} déstocke cet entrepôt.</flux:text>
                </div>
                <div class="grid gap-5 lg:grid-cols-5">
                    <div class="space-y-2 lg:col-span-3">
                        <flux:input wire:model.live.debounce.300ms="stockSearch" icon="magnifying-glass" size="sm" placeholder="Rechercher un article…" />
                        <div class="max-h-96 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-800"><tr><th class="px-3 py-2 text-left font-medium">Article</th><th class="px-3 py-2 text-right font-medium">Stock</th><th class="px-3 py-2 text-right font-medium">Min.</th></tr></thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($this->warehouseStock as $level)
                                        <tr wire:key="ws-{{ $level->id }}"><td class="px-3 py-1.5">{{ $level->product->name }}</td>
                                            <td @class(['px-3 py-1.5 text-right font-medium tabular-nums', 'text-red-600' => (float) $level->quantity <= (float) $level->product->min_stock])>{{ (float) $level->quantity }} {{ $level->product->unit?->symbol }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums text-zinc-500">{{ (float) $level->product->min_stock }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="space-y-4 lg:col-span-2">
                        <div>
                            <flux:heading size="sm">Transferts attendus</flux:heading>
                            <ul class="mt-2 space-y-2 text-sm">
                                @forelse ($this->incomingTransfers as $transfer)
                                    <li class="rounded-lg border border-zinc-200 p-2 dark:border-zinc-700" wire:key="in-{{ $transfer->id }}">
                                        <div class="flex items-center justify-between"><span class="font-medium">{{ $transfer->number }}</span><x-status-badge :status="$transfer->status" /></div>
                                        <div class="text-xs text-zinc-500">Depuis {{ $transfer->from->name }} · {{ $transfer->items->map(fn ($i) => (float) $i->quantity_requested.' × '.$i->product->name)->implode(', ') }}</div>
                                        @if ($transfer->status->value === 'in_transit')
                                            @can('inventory.update')<flux:button size="xs" variant="primary" icon="inbox-arrow-down" class="mt-2" wire:click="receiveTransfer({{ $transfer->id }})">Réceptionner</flux:button>@endcan
                                        @endif
                                    </li>
                                @empty
                                    <li class="text-zinc-500">Aucun transfert en attente.</li>
                                @endforelse
                            </ul>
                        </div>
                        @can('inventory.create')
                            <form wire:submit="requestRestock" class="space-y-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                                <flux:heading size="sm">Demander un réassort</flux:heading>
                                <flux:select wire:model="restockFromId" size="sm">
                                    @foreach (\App\Models\Warehouse::query()->where('id', '!=', $this->session->register->warehouse_id)->where('is_active', true)->get() as $warehouse)
                                        <flux:select.option :value="$warehouse->id">Depuis {{ $warehouse->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @foreach ($restockLines as $index => $line)
                                    <div class="grid grid-cols-3 gap-2" wire:key="rs-{{ $index }}">
                                        <flux:select wire:model="restockLines.{{ $index }}.product_id" size="sm" class="col-span-2"><flux:select.option value="">Article…</flux:select.option>@foreach ($this->warehouseStock as $level)<flux:select.option :value="$level->product_id">{{ $level->product->name }}</flux:select.option>@endforeach</flux:select>
                                        <flux:input wire:model="restockLines.{{ $index }}.quantity" type="number" step="any" size="sm" placeholder="Qté" />
                                    </div>
                                @endforeach
                                <flux:error name="restockLines.*.product_id" />
                                <div class="flex gap-2">
                                    <flux:button type="button" size="xs" icon="plus" wire:click="addRestockLine">Ligne</flux:button>
                                    <flux:button type="submit" size="xs" variant="primary" icon="paper-airplane">Envoyer la demande</flux:button>
                                </div>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
