<div class="space-y-6">
    <x-page-header title="Livraisons" subtitle="À livrer → En cours → Livrée · Échec · Reportée" />

    <div class="panel p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="status" class="max-w-52">
                <flux:select.option value="active">À traiter</flux:select.option>
                <flux:select.option value="">Toutes</flux:select.option>
                @foreach ($statuses as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
            </flux:select>
            <flux:switch wire:model.live="mineOnly" label="Mes livraisons uniquement" />
        </div>
        <x-filter-bar class="border-0 p-0 shadow-none" placeholder="N° de bon, commande, destinataire, ville…" period-label="Prévue" />

        <flux:table :paginate="$deliveries">
            <flux:table.columns>
                <flux:table.column>Bon</flux:table.column>
                <flux:table.column>Commande / client</flux:table.column>
                <flux:table.column>Mode</flux:table.column>
                <flux:table.column>Adresse</flux:table.column>
                <flux:table.column>Prévue</flux:table.column>
                <flux:table.column>Livreur</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($deliveries as $delivery)
                    <flux:table.row :key="$delivery->id">
                        <flux:table.cell class="font-medium">{{ $delivery->number }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('orders.show', $delivery->order)" wire:navigate>{{ $delivery->order->number }}</flux:link>
                            <div class="text-xs text-zinc-500">{{ $delivery->recipient_name }} · {{ $delivery->phone }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $delivery->mode->label() }}</flux:table.cell>
                        <flux:table.cell class="max-w-48 truncate">{{ $delivery->address }} {{ $delivery->city }}</flux:table.cell>
                        <flux:table.cell>{{ $delivery->scheduled_at?->format('d/m H:i') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $delivery->driver?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell><x-status-badge :status="$delivery->status" /></flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" icon="printer" variant="ghost" :href="route('print.delivery-note', $delivery)" target="_blank" />
                                @can('deliveries.update')
                                    @if (in_array($delivery->status->value, ['pending', 'postponed', 'failed']) && $delivery->mode->value !== 'pickup')
                                        <flux:button size="xs" icon="truck" wire:click="start({{ $delivery->id }})">Partir</flux:button>
                                    @endif
                                    @if ($delivery->status->value !== 'delivered')
                                        <flux:button size="xs" variant="primary" icon="check" wire:click="openComplete({{ $delivery->id }})">Clôturer</flux:button>
                                    @endif
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="truck" title="Aucune livraison" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="complete" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Preuve de livraison</flux:heading>
            <flux:input type="file" wire:model="signature" label="Signature du client (photo / scan)" accept="image/*" />
            <flux:input type="file" wire:model="photo" label="Photo (facultatif)" accept="image/*" capture="environment" />
            <flux:textarea wire:model="observations" label="Observations" rows="2" />
            <flux:button variant="primary" icon="check-circle" wire:click="complete" class="w-full">Confirmer la livraison</flux:button>
            <flux:separator text="ou" />
            <flux:input wire:model="reason" label="Motif d’échec / report" />
            <flux:input wire:model="postponeTo" type="datetime-local" label="Reporter au (laisser vide = échec)" />
            <flux:button variant="danger" icon="x-circle" wire:click="fail" class="w-full">Enregistrer l’échec / le report</flux:button>
        </div>
    </flux:modal>
</div>
