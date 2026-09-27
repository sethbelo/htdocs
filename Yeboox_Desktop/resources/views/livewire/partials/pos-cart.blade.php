<div class="flex min-h-0 flex-1 flex-col">
    <div class="shrink-0 border-b border-zinc-200 p-4 dark:border-zinc-800">
        <div class="flex items-center justify-between gap-3"><div><flux:heading size="lg">Commande en cours</flux:heading><flux:text size="sm">{{ number_format($this->cartCount(), 0) }} article(s)</flux:text></div>@if($cart)<flux:button wire:click="clearCart" variant="subtle" icon="trash" square tooltip="Vider le panier" />@endif</div>
        <div class="mt-4 grid grid-cols-3 gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
            @foreach(['dine_in' => ['Table', 'building-storefront'], 'takeaway' => ['Emporter', 'shopping-bag'], 'delivery' => ['Livraison', 'truck']] as $value => [$label, $icon])
                <button type="button" wire:click="setFulfillmentType('{{ $value }}')" class="flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-[11px] font-semibold transition {{ $fulfillmentType === $value ? 'bg-white text-violet-700 shadow-sm dark:bg-zinc-700 dark:text-violet-300' : 'text-zinc-500' }}"><flux:icon :name="$icon" class="size-4" />{{ $label }}</button>
            @endforeach
        </div>
        <div class="mt-3 space-y-3">
            @if($fulfillmentType === 'dine_in')
                <flux:button wire:click="openTableSelector" icon="table-cells" icon:trailing="chevron-right" class="w-full justify-start">{{ $tableName ?: 'Choisir une table' }}<flux:spacer />@if($tableName && $this->tableHasOrder($tableName))<flux:badge color="amber" size="sm">Occupée</flux:badge>@elseif($tableName)<flux:badge color="emerald" size="sm">Libre</flux:badge>@endif</flux:button>
            @elseif($fulfillmentType === 'delivery')
                <div class="grid grid-cols-2 gap-2"><flux:input wire:model="deliveryPhone" size="sm" placeholder="Téléphone" icon="phone" /><flux:input wire:model="deliveryAddress" size="sm" placeholder="Adresse" icon="map-pin" /></div>
            @endif
            <flux:select wire:model="customerId" size="sm" placeholder="Client de passage"><flux:select.option value="">Client de passage</flux:select.option>@foreach($customers as $customer)<flux:select.option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</flux:select.option>@endforeach</flux:select>
            @if($loyaltyEnabled && $loyaltyMode === 'services' && $selectedCustomer)
                <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-500/10">
                    <div class="flex items-center justify-between gap-3"><span class="flex min-w-0 items-center gap-2 text-xs font-semibold text-emerald-800 dark:text-emerald-200"><flux:icon.gift class="size-4 shrink-0" /><span class="truncate">{{ $selectedCustomer->loyalty_service_count }}/{{ $loyaltyRequiredServices }} · Carte cadeau : {{ $selectedCustomer->loyalty_reward_balance > 0 ? '1 offerte' : '0' }}</span></span>@if($selectedCustomer->loyalty_reward_balance > 0 && $loyaltyRewardProduct)<flux:button size="sm" :variant="$useLoyaltyReward ? 'primary' : 'outline'" color="emerald" :icon="$useLoyaltyReward ? 'check' : 'gift'" wire:click="{{ $useLoyaltyReward ? 'removeLoyaltyReward' : 'applyLoyaltyReward' }}">{{ $useLoyaltyReward ? 'Appliquée' : 'Utiliser' }}</flux:button>@endif</div>
                    @if($loyaltyRewardProduct)<div class="mt-1 text-[11px] text-emerald-700/80 dark:text-emerald-300/70">{{ $loyaltyRewardProduct->type === 'service' ? 'Prestation' : 'Produit' }} offert : {{ $loyaltyRewardProduct->name }}</div>@endif
                </div>
            @endif
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto p-3">
        @forelse($cart as $line)
            <div wire:key="cart-{{ $line['id'] }}" class="mb-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="flex items-start gap-2"><div class="min-w-0 flex-1"><div class="truncate text-sm font-semibold">{{ $line['name'] }}</div><div class="mt-0.5 text-xs text-zinc-500">{{ $this->displayAmount($this->cartLineGrossUnit($line)) }} TTC · {{ $line['sku'] }}</div></div><button type="button" wire:click="remove({{ $line['id'] }})" class="text-zinc-400 hover:text-rose-600"><flux:icon.x-mark class="size-4" /></button></div>
                <div class="mt-3 flex items-center justify-between"><div class="flex items-center rounded-lg border border-zinc-200 bg-white p-0.5 dark:border-zinc-700 dark:bg-zinc-900"><button type="button" wire:click="decrement({{ $line['id'] }})" class="grid size-7 place-items-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon.minus class="size-3.5" /></button><span class="min-w-8 text-center text-xs font-semibold">{{ number_format((float) $line['quantity'], 0) }}</span><button type="button" wire:click="increment({{ $line['id'] }})" class="grid size-7 place-items-center rounded-md text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon.plus class="size-3.5" /></button></div><span class="text-sm font-bold">{{ $this->displayAmount($this->cartLineGrossTotal($line)) }}</span></div>
            </div>
        @empty
            <div class="grid h-full min-h-48 place-items-center text-center"><div><span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><flux:icon.shopping-cart class="size-5" /></span><div class="mt-3 text-sm font-semibold">Panier vide</div><div class="mt-1 text-xs text-zinc-500">Touchez un article pour l’ajouter.</div></div></div>
        @endforelse
    </div>

    <div class="shrink-0 space-y-3 border-t border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="space-y-2 text-sm"><div class="flex justify-between text-zinc-500"><span>Sous-total</span><span>{{ $this->displayAmount($this->subtotal()) }}</span></div><div class="flex justify-between text-zinc-500"><span>Taxes</span><span>{{ $this->displayAmount($this->taxTotal()) }}</span></div>@if($this->loyaltyRewardSubtotalDiscount() + $this->loyaltyRewardTaxDiscount() > 0)<div class="flex justify-between font-semibold text-emerald-600"><span class="flex items-center gap-1.5"><flux:icon.gift class="size-4" />Carte cadeau offerte</span><span>− {{ $this->displayAmount($this->loyaltyRewardSubtotalDiscount() + $this->loyaltyRewardTaxDiscount()) }}</span></div>@endif<div class="flex items-center justify-between gap-3 text-zinc-500"><span>Remise</span><div class="w-28"><flux:input type="number" min="0" step="0.01" wire:model.live.debounce.300ms="discountAmount" size="sm" /></div></div><div class="flex items-end justify-between border-t border-dashed border-zinc-300 pt-3 dark:border-zinc-700"><span class="font-semibold">Total</span><span class="text-xl font-bold text-violet-700 dark:text-violet-300">{{ $this->displayAmount($this->total()) }}</span></div></div>
        <div class="grid grid-cols-[auto_1fr] gap-2"><flux:button wire:click="openAddition" icon="printer" tooltip="Imprimer l’addition" :disabled="empty($cart)">Addition</flux:button><flux:button wire:click="startPayment" variant="primary" color="violet" icon="credit-card" class="w-full" :disabled="empty($cart)">Encaisser</flux:button></div>
    </div>
</div>
