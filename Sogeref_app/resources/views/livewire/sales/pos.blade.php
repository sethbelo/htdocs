<div>
    @if (! $session)
        {{-- ═══ OUVERTURE DU POINT DE VENTE ══════════════════════════════════ --}}
        <x-ui.page-header title="Point de vente" eyebrow="Showroom" subtitle="Vente comptoir du mobilier SOGEREF, fidélité et ticket de caisse." />
        <div class="mx-auto max-w-md">
            <x-ui.card title="Ouvrir la caisse du showroom" icon="lock-open">
                <div class="space-y-4">
                    <x-ui.select label="Caisse" wire:model="accountId" :options="$cashAccounts->pluck('name', 'id')" />
                    <x-ui.input label="Fonds de caisse" type="number" step="0.01" wire:model="openingAmount" suffix="USD" />
                    <x-ui.button variant="primary" size="lg" icon="lock-open" class="w-full" wire:click="openSession">Commencer les ventes</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-[1fr_340px] lg:items-start xl:grid-cols-[1fr_400px]">
            {{-- ═══ 1. CATALOGUE ═════════════════════════════════════════════ --}}
            <section class="min-w-0">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <label class="relative min-w-60 flex-1">
                        <x-ui.icon name="magnifying-glass" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-fg-subtle" />
                        <input type="search" wire:model.live.debounce.250ms="search" autofocus class="input h-12 pl-11 text-[15px]" placeholder="Rechercher ou scanner un code-barres…">
                    </label>
                    <div class="flex items-center gap-2 text-[12.5px] text-fg-muted">
                        <x-ui.badge tone="success" dot>{{ $session->reference }}</x-ui.badge>
                        <span>{{ money($sessionTotal) }} vendus</span>
                        <x-ui.button variant="ghost" size="sm" icon="lock-closed" wire:click="closeSession" wire:confirm="Clôturer la session du point de vente ?">Clôturer</x-ui.button>
                    </div>
                </div>
                <div class="mb-4 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <button type="button" wire:click="$set('categoryId', null)" @class(['btn btn-sm shrink-0', 'btn-primary' => ! $categoryId, 'btn-secondary' => $categoryId])>Tout</button>
                    @foreach ($categories as $c)
                        <button type="button" wire:click="$set('categoryId', {{ $c->id }})" @class(['btn btn-sm shrink-0', 'btn-primary' => $categoryId === $c->id, 'btn-secondary' => $categoryId !== $c->id])>{{ $c->name }}</button>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    @forelse ($products as $p)
                        <button type="button" wire:click="add({{ $p->id }})" wire:key="pp-{{ $p->id }}" class="pos-tile">
                            <span class="grid aspect-[4/3] place-items-center bg-gradient-to-br from-brand-50 to-surface-2 dark:from-white/5 dark:to-transparent">
                                @if ($p->imageUrl())<img src="{{ $p->imageUrl() }}" class="size-full object-cover" alt="">@else<x-ui.icon name="cube" class="size-10 text-brand-300" />@endif
                            </span>
                            <span class="flex flex-1 flex-col p-3">
                                <span class="line-clamp-2 text-[13px] leading-snug font-semibold">{{ $p->name }}</span>
                                <span class="mt-auto pt-2">
                                    <span class="block font-display text-[16px] font-semibold whitespace-nowrap text-brand-600 dark:text-lagoon-300">{{ money($p->sale_price) }}</span>
                                    @if ($p->track_stock)<span @class(['block text-[11px]', 'text-flame-600 font-semibold' => $p->stock_quantity <= 0, 'text-fg-subtle' => $p->stock_quantity > 0])>{{ qty($p->stock_quantity) }} en stock</span>@endif
                                </span>
                            </span>
                        </button>
                    @empty
                        <div class="col-span-full card"><x-ui.empty icon="cube" title="Aucun article" text="Rendez des articles visibles au point de vente depuis Stock & entrepôts." /></div>
                    @endforelse
                </div>
            </section>

            {{-- ═══ 2. PANIER ════════════════════════════════════════════════ --}}
            <aside class="card flex flex-col lg:sticky lg:top-20 lg:max-h-[calc(100dvh-6rem)]">
                <div class="border-b border-line p-4">
                    @if ($customer)
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$customer->display_name" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">{{ $customer->display_name }}</p>
                                <p class="text-[12px] text-fg-subtle">@if ($totals['card']){{ $totals['card']->tier }} · {{ $totals['card']->points }} pts @if ($totals['tier']) · remise {{ qty($totals['tier']) }} % @endif @else Sans carte de fidélité @endif</p>
                            </div>
                            <button type="button" wire:click="selectCustomer(null)" class="btn btn-ghost btn-sm btn-icon"><x-ui.icon name="x-mark" class="size-4" /></button>
                        </div>
                    @else
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="customerSearch" class="input" placeholder="Client (nom ou téléphone) — facultatif">
                            @if ($customers->isNotEmpty())
                                <ul class="card absolute inset-x-0 z-10 mt-1 p-1 shadow-[var(--shadow-pop)]">
                                    @foreach ($customers as $c)<li><button type="button" wire:click="selectCustomer({{ $c->id }})" class="w-full rounded-lg px-3 py-2 text-left text-[13px] hover:bg-surface-2">{{ $c->display_name }} <span class="text-fg-subtle">{{ $c->phone }}</span></button></li>@endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
                <ul class="flex-1 divide-y divide-line overflow-y-auto">
                    @forelse ($cart as $key => $line)
                        <li class="flex items-center gap-3 px-4 py-3" wire:key="c-{{ $key }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-semibold">{{ $line['name'] }}</p>
                                <p class="text-[12px] text-fg-subtle">{{ money($line['unit_price']) }} @if ($line['track'] && $line['quantity'] > $line['stock'])· <span class="text-flame-600">stock {{ qty($line['stock']) }}</span>@endif</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="increment('{{ $key }}', -1)" class="btn btn-secondary btn-sm btn-icon">−</button>
                                <span class="w-7 text-center font-semibold tabular-nums">{{ $line['quantity'] }}</span>
                                <button type="button" wire:click="increment('{{ $key }}', 1)" class="btn btn-secondary btn-sm btn-icon">+</button>
                            </div>
                            <span class="w-20 text-right font-semibold tabular-nums">{{ money($line['quantity'] * $line['unit_price']) }}</span>
                        </li>
                    @empty
                        <li class="px-4 py-12 text-center text-[13px] text-fg-subtle">Touchez un article pour l’ajouter au panier.</li>
                    @endforelse
                </ul>
                <div class="space-y-1.5 border-t border-line p-4 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-fg-muted">Sous-total</span><span class="tabular-nums">{{ money($totals['subtotal']) }}</span></div>
                    @if ($totals['discount'] > 0)<div class="flex justify-between text-emerald-600"><span>Remise fidélité</span><span class="tabular-nums">− {{ money($totals['discount']) }}</span></div>@endif
                    <div class="flex justify-between"><span class="text-fg-muted">TVA</span><span class="tabular-nums">{{ money($totals['tax']) }}</span></div>
                    @if ($totals['card'] && $totals['card']->points >= $totals['card']->program->min_redeem_points)
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-sun-50 px-2.5 py-1.5 dark:bg-sun-400/10">
                            <label class="text-[12.5px]">Utiliser des points ({{ $totals['card']->points }} dispo.)</label>
                            <input type="number" step="50" min="0" max="{{ $totals['card']->points }}" wire:model.live.debounce.400ms="redeemPoints" class="input h-8 w-24 text-right">
                        </div>
                        @if ($totals['points'] > 0)<div class="flex justify-between text-emerald-600"><span>Points convertis</span><span class="tabular-nums">− {{ money($totals['points']) }}</span></div>@endif
                    @endif
                    <div class="flex items-baseline justify-between pt-2"><span class="font-semibold">Total</span><span class="font-display text-[28px] font-semibold tabular-nums">{{ money($totals['total']) }}</span></div>
                    <div class="grid grid-cols-[auto_1fr] gap-2 pt-2">
                        <x-ui.button variant="ghost" icon="trash" wire:click="clear" :disabled="! $cart">Vider</x-ui.button>
                        <x-ui.button variant="accent" size="lg" icon="banknotes" wire:click="checkout" :disabled="! $cart">Encaisser</x-ui.button>
                    </div>
                    @if ($lastOrderId)
                        <a href="{{ route('pdf.ticket', $lastOrderId) }}" target="_blank" class="btn btn-soft btn-sm mt-1 w-full"><x-ui.icon name="printer" class="size-4" /> Imprimer le dernier ticket</a>
                    @endif
                </div>
            </aside>
        </div>

        {{-- ═══ 3. PAIEMENT ══════════════════════════════════════════════════ --}}
        <x-ui.modal wire:model="showPayment" title="Encaissement" icon="banknotes" tone="sun" width="max-w-md">
            <p class="font-display text-[34px] font-semibold text-fg">{{ money($totals['total']) }}</p>
            <div class="mt-4 grid grid-cols-3 gap-2">
                @foreach (['cash' => ['Espèces', 'banknotes'], 'mobile_money' => ['Mobile money', 'device-phone-mobile'], 'card' => ['Carte', 'credit-card']] as $key => [$label, $icon])
                    <button type="button" wire:click="$set('method', '{{ $key }}')" @class(['flex flex-col items-center gap-1 rounded-xl border p-3 text-[12.5px] font-semibold', 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-white/8 dark:text-white' => $method === $key, 'border-line text-fg-muted' => $method !== $key])><x-ui.icon :name="$icon" />{{ $label }}</button>
                @endforeach
            </div>
            @if ($method === 'cash')
                <x-ui.input label="Montant reçu" type="number" step="0.01" wire:model.live.debounce.300ms="tendered" class="mt-4" suffix="USD" />
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ([ceil($totals['total']), ceil($totals['total'] / 10) * 10, ceil($totals['total'] / 50) * 50, ceil($totals['total'] / 100) * 100] as $v)
                        <button type="button" wire:click="$set('tendered', '{{ $v }}')" class="btn btn-secondary btn-sm">{{ money($v, 'USD', 0) }}</button>
                    @endforeach
                </div>
                @php $change = (float) $tendered - $totals['total']; @endphp
                <p @class(['mt-3 rounded-lg px-3 py-2 font-semibold', 'bg-emerald-50 text-emerald-700' => $change >= 0, 'bg-flame-50 text-flame-700' => $change < 0])>{{ $change >= 0 ? 'Rendu : '.money($change) : 'Manque : '.money(abs($change)) }}</p>
            @endif
            <x-slot:footer>
                <x-ui.button variant="ghost" x-on:click="open = false">Retour</x-ui.button>
                <x-ui.button variant="primary" size="lg" icon="check" wire:click="pay">Valider la vente</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
