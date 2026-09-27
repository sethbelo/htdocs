<div class="space-y-6" x-data="scanner((code) => $wire.lookup(code))" @keydown.window="onKey($event)">
    <x-page-header title="Caisse — encaissement des commandes" subtitle="Scannez le ticket (QR ou code-barres) ou saisissez le numéro de commande.">
        <x-slot:actions>
            @include('livewire.cash.partials.session-bar')
        </x-slot:actions>
    </x-page-header>

    @if (! $this->session)
        @include('livewire.cash.partials.open-session')
    @else
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <form wire:submit="lookup" class="panel flex gap-2 p-4">
                    <flux:input wire:model="reference" icon="qr-code" placeholder="CMD-{{ now()->year }}-000001" class="flex-1" autofocus />
                    <flux:button type="submit" variant="primary" icon="magnifying-glass">Rechercher</flux:button>
                </form>

                @if ($lastCheckout)
                    <flux:callout variant="success" icon="check-circle" :heading="'Commande '.$lastCheckout['order_number'].' encaissée'">
                        @if ($lastCheckout['change'] > 0)
                            <flux:callout.text>Monnaie à rendre : <strong><x-money :amount="$lastCheckout['change']" /></strong></flux:callout.text>
                        @endif
                        <x-slot name="actions">
                            <flux:button size="sm" icon="printer" :href="route('print.invoice', [$lastCheckout['invoice_id'], 'ticket'])" target="_blank">Ticket</flux:button>
                            <flux:button size="sm" icon="document" :href="route('print.invoice', [$lastCheckout['invoice_id'], 'a4'])" target="_blank">Facture A4</flux:button>
                            <flux:button size="sm" icon="document" :href="route('print.invoice', [$lastCheckout['invoice_id'], 'a5'])" target="_blank">A5</flux:button>
                        </x-slot>
                    </flux:callout>
                @endif

                @if ($this->order)
                    @php($order = $this->order)
                    <div class="panel p-4 space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <flux:heading size="lg">{{ $order->number }}</flux:heading>
                                <flux:text>{{ $order->client->displayName() }} · {{ $order->client->phone }} · commercial {{ $order->salesRep?->name }}</flux:text>
                            </div>
                            <x-status-badge :status="$order->status" size="lg" />
                        </div>
                        <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            @foreach ($order->items as $item)
                                <li class="flex justify-between py-2" wire:key="it-{{ $item->id }}">
                                    <div><div class="font-medium">{{ $item->description }}</div><div class="text-xs text-zinc-500">{{ (float) $item->quantity }} × <x-money :amount="$item->unit_price" /> · {{ $item->specSummary() }}</div></div>
                                    <x-money :amount="$item->total" />
                                </li>
                            @endforeach
                        </ul>
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            <x-kpi label="Total" :value="\App\Services\Support\Money::format($order->total)" />
                            <x-kpi label="Acompte exigé" :value="\App\Services\Support\Money::format($order->deposit_required)" />
                            <x-kpi label="Déjà payé" :value="\App\Services\Support\Money::format($order->amount_paid)" color="emerald" />
                            <x-kpi label="Reste à payer" :value="\App\Services\Support\Money::format($order->balanceDue())" color="amber" />
                        </div>
                    </div>

                    @if ($order->balanceDue() > 0 && ! $order->status->isTerminal() && $order->status !== \App\Enums\OrderStatus::Suspended)
                        <form wire:submit="checkout" class="panel p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <flux:heading>Paiement</flux:heading>
                                <div class="flex gap-2">
                                    <flux:button size="sm" wire:click="payFull" type="button">Solde complet</flux:button>
                                    <flux:button size="sm" icon="plus" wire:click="addPayment" type="button">Paiement mixte</flux:button>
                                </div>
                            </div>
                            @foreach ($payments as $index => $payment)
                                <div class="grid gap-3 md:grid-cols-12" wire:key="pay-{{ $index }}">
                                    <flux:select wire:model.live="payments.{{ $index }}.method" class="md:col-span-4">
                                        @foreach ($methods as $value => $label)
                                            @if ($value !== 'credit' || $order->client->allow_credit)
                                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                                            @endif
                                        @endforeach
                                    </flux:select>
                                    <flux:input wire:model.live.debounce.300ms="payments.{{ $index }}.amount" type="number" step="0.01" class="md:col-span-3" />
                                    <flux:input wire:model="payments.{{ $index }}.reference" placeholder="Référence (transaction, chèque…)" class="md:col-span-4" />
                                    @if (count($payments) > 1)
                                        <flux:button icon="trash" variant="ghost" wire:click="removePayment({{ $index }})" type="button" class="md:col-span-1" />
                                    @endif
                                </div>
                            @endforeach
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                                <div class="text-sm">Remis : <strong><x-money :amount="$this->tendered" /></strong>
                                    @if ($this->tendered > $order->balanceDue())
                                        · Monnaie estimée : <strong class="text-emerald-600"><x-money :amount="$this->tendered - $order->balanceDue()" /></strong>
                                    @elseif ($this->tendered < $order->balanceDue())
                                        · Reste après paiement : <strong class="text-amber-600"><x-money :amount="$order->balanceDue() - $this->tendered" /></strong>
                                    @endif
                                </div>
                                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">Valider l’encaissement</flux:button>
                            </div>
                            <flux:error name="payments.*.amount" />
                        </form>
                    @else
                        <flux:callout icon="information-circle" heading="Rien à encaisser pour cette commande." />
                    @endif
                @endif
            </div>

            <div class="panel p-4">
                <flux:heading>En attente de paiement</flux:heading>
                <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->awaiting as $pending)
                        <li wire:key="aw-{{ $pending->id }}">
                            <button type="button" wire:click="selectOrder({{ $pending->id }})" class="flex w-full items-center justify-between py-2 text-left text-sm hover:text-indigo-600">
                                <div class="min-w-0"><div class="font-medium">{{ $pending->number }}</div><div class="truncate text-xs text-zinc-500">{{ $pending->client->displayName() }} · {{ $pending->ordered_at->diffForHumans() }}</div></div>
                                <x-money :amount="$pending->balanceDue()" class="font-medium" />
                            </button>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-zinc-500">Aucune commande en attente.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
