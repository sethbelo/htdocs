<div class="space-y-6">
    <x-page-header title="Devis & factures pro forma" subtitle="Devis → Commande → Facture → Encaissement">
        <x-slot:actions>
            @can('quotes.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="create">Nouveau devis</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="panel p-4 space-y-4">
        <div class="grid gap-3 md:grid-cols-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N°, client…" class="md:col-span-2" />
            <flux:select wire:model.live="status">
                <flux:select.option value="">Tous statuts</flux:select.option>
                @foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach
            </flux:select>
        </div>
        <flux:table :paginate="$quotes">
            <flux:table.columns>
                <flux:table.column>N°</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Client</flux:table.column>
                <flux:table.column>Validité</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column align="end">Total</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($quotes as $quote)
                    <flux:table.row :key="$quote->id">
                        <flux:table.cell class="font-medium">{{ $quote->number }}</flux:table.cell>
                        <flux:table.cell>{{ $quote->type->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $quote->client->displayName() }}</flux:table.cell>
                        <flux:table.cell>{{ $quote->valid_until?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell><x-status-badge :status="$quote->status" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$quote->total" /></flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="printer" :href="route('print.quote', $quote)" target="_blank" />
                                @if ($quote->order)
                                    <flux:button size="xs" :href="route('orders.show', $quote->order)" wire:navigate>{{ $quote->order->number }}</flux:button>
                                @else
                                    @can('quotes.update')
                                        <flux:dropdown align="end">
                                            <flux:button size="xs" icon="ellipsis-horizontal" />
                                            <flux:menu>
                                                <flux:menu.item wire:click="setStatus({{ $quote->id }}, 'sent')">Marquer envoyé</flux:menu.item>
                                                <flux:menu.item wire:click="setStatus({{ $quote->id }}, 'accepted')">Marquer accepté</flux:menu.item>
                                                <flux:menu.item wire:click="setStatus({{ $quote->id }}, 'rejected')">Marquer refusé</flux:menu.item>
                                                @can('orders.create')<flux:menu.item icon="arrow-right-circle" wire:click="convert({{ $quote->id }})">Convertir en commande</flux:menu.item>@endcan
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endcan
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="document-text" title="Aucun devis" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="quote" class="w-full max-w-3xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Nouveau devis / pro forma</flux:heading>
            <div class="grid gap-3 md:grid-cols-4">
                <flux:select wire:model="form.type" label="Type">
                    @foreach (\App\Enums\QuoteType::options() as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="form.client_id" label="Client" class="md:col-span-2">
                    <flux:select.option value="">Choisir…</flux:select.option>
                    @foreach ($clients as $client)<flux:select.option :value="$client->id">{{ $client->displayName() }}</flux:select.option>@endforeach
                </flux:select>
                <flux:input wire:model="form.valid_until" type="date" label="Valable jusqu’au" />
            </div>
            @foreach ($items as $index => $item)
                <div class="grid gap-2 md:grid-cols-12" wire:key="qi-{{ $index }}">
                    <flux:input wire:model="items.{{ $index }}.description" placeholder="Désignation" class="md:col-span-4" />
                    <flux:input wire:model="items.{{ $index }}.format" placeholder="Format" class="md:col-span-1" />
                    <flux:input wire:model="items.{{ $index }}.paper" placeholder="Papier" class="md:col-span-2" />
                    <flux:input wire:model="items.{{ $index }}.quantity" type="number" step="any" placeholder="Qté" class="md:col-span-1" />
                    <flux:input wire:model="items.{{ $index }}.unit_price" type="number" step="0.01" placeholder="P.U." class="md:col-span-2" />
                    <flux:input wire:model="items.{{ $index }}.tax_rate" type="number" step="0.01" placeholder="TVA" class="md:col-span-1" />
                    <flux:button type="button" variant="ghost" icon="trash" wire:click="removeItem({{ $index }})" class="md:col-span-1" />
                </div>
            @endforeach
            <flux:button type="button" size="sm" icon="plus" wire:click="addItem">Ligne</flux:button>
            <flux:textarea wire:model="form.notes" label="Conditions / notes" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Créer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
