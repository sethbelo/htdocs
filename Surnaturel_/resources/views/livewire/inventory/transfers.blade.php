<div class="space-y-6">
    <x-page-header title="Transferts entre entrepôts" subtitle="Demande → expédition (sortie source) → réception (entrée destination), avec convoyeur et écarts.">
        <x-slot:actions>
            @can('inventory.create')<flux:button size="sm" variant="primary" icon="arrows-right-left" wire:click="create">Nouveau transfert</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach (\App\Enums\StockTransferStatus::cases() as $case)
            <button wire:click="$set('status', '{{ $status === $case->value ? '' : $case->value }}')" @class(['kpi-card text-left', 'ring-2 ring-indigo-500' => $status === $case->value]) wire:key="st-{{ $case->value }}">
                <flux:badge size="sm" :color="$case->color()">{{ $case->label() }}</flux:badge>
                <div class="mt-2 text-2xl font-semibold">{{ $counts[$case->value] ?? 0 }}</div>
            </button>
        @endforeach
    </div>

    <div class="panel p-4">
        <flux:table :paginate="$transfers">
            <flux:table.columns>
                <flux:table.column>Transfert</flux:table.column>
                <flux:table.column>De → vers</flux:table.column>
                <flux:table.column>Articles</flux:table.column>
                <flux:table.column>Demandé par</flux:table.column>
                <flux:table.column>Expédié / convoyeur</flux:table.column>
                <flux:table.column>Réceptionné</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($transfers as $transfer)
                    <flux:table.row :key="$transfer->id">
                        <flux:table.cell><button wire:click="open({{ $transfer->id }})" class="font-medium hover:text-indigo-600">{{ $transfer->number }}</button><div class="text-xs text-zinc-500">{{ $transfer->requested_at->format('d/m/Y H:i') }}</div></flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $transfer->from->name }} <span class="text-zinc-400">→</span> {{ $transfer->to->name }}</flux:table.cell>
                        <flux:table.cell>{{ $transfer->items->count() }} · {{ (float) $transfer->items->sum('quantity_requested') }} u.</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $transfer->requester->name }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $transfer->shipper?->name ?? '—' }}<div class="text-xs text-zinc-500">{{ $transfer->carrier_name }} {{ $transfer->shipped_at?->format('d/m H:i') }}</div></flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $transfer->receiver?->name ?? '—' }}<div class="text-xs text-zinc-500">{{ $transfer->received_at?->format('d/m H:i') }}</div></flux:table.cell>
                        <flux:table.cell>
                            <x-status-badge :status="$transfer->status" />
                            @if ($transfer->status->value === 'received' && $transfer->hasDiscrepancy())<flux:badge size="sm" color="red">Écart</flux:badge>@endif
                        </flux:table.cell>
                        <flux:table.cell><flux:button size="xs" variant="ghost" icon="printer" :href="route('print.stock-transfer', $transfer)" target="_blank" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="arrows-right-left" title="Aucun transfert" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="transfer" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Demande de transfert</flux:heading>
            <div class="grid gap-3 md:grid-cols-2">
                <flux:select wire:model="form.from_warehouse_id" label="Depuis l’entrepôt"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($allWarehouses as $w)<flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.to_warehouse_id" label="Vers l’entrepôt"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($allWarehouses as $w)<flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>@endforeach</flux:select>
            </div>
            @foreach ($lines as $index => $line)
                <div class="grid grid-cols-12 gap-2" wire:key="tr-line-{{ $index }}">
                    <flux:select wire:model="lines.{{ $index }}.product_id" class="col-span-9"><flux:select.option value="">Article…</flux:select.option>@foreach ($products as $p)<flux:select.option :value="$p->id">{{ $p->name }} ({{ $p->sku }})</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="lines.{{ $index }}.quantity" type="number" step="any" placeholder="Qté" class="col-span-3" />
                </div>
            @endforeach
            <flux:error name="lines.*.product_id" />
            <flux:button type="button" size="sm" icon="plus" wire:click="addLine">Article</flux:button>
            <flux:textarea wire:model="form.notes" label="Observations" rows="2" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Envoyer la demande</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="process" class="w-full max-w-2xl">
        @if ($current)
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">{{ $current->number }}</flux:heading>
                        <flux:text>{{ $current->from->name }} → {{ $current->to->name }} · demandé par {{ $current->requester->name }}</flux:text>
                    </div>
                    <x-status-badge :status="$current->status" size="lg" />
                </div>
                <table class="w-full text-sm">
                    <thead class="text-xs text-zinc-500"><tr><th class="text-left font-medium">Article</th><th class="text-right font-medium">Demandé</th><th class="text-right font-medium">Expédié</th><th class="text-right font-medium">Reçu</th><th class="w-28"></th></tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($current->items as $item)
                            <tr wire:key="ti-{{ $item->id }}">
                                <td class="py-2">{{ $item->product->name }}</td>
                                <td class="text-right tabular-nums">{{ (float) $item->quantity_requested }}</td>
                                <td class="text-right tabular-nums">{{ $item->quantity_shipped !== null ? (float) $item->quantity_shipped : '—' }}</td>
                                <td @class(['text-right tabular-nums', 'text-red-600 font-medium' => $item->discrepancy() != 0])>{{ $item->quantity_received !== null ? (float) $item->quantity_received : '—' }}</td>
                                <td class="ps-2">@if (in_array($current->status->value, ['requested', 'in_transit']))<flux:input wire:model="quantities.{{ $item->id }}" type="number" step="any" size="sm" />@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($current->carrier_name)<flux:text size="sm">Convoyeur : <strong>{{ $current->carrier_name }}</strong> · expédié par {{ $current->shipper?->name }} le {{ $current->shipped_at?->format('d/m/Y H:i') }}</flux:text>@endif
                @if ($current->reception_notes)<flux:callout icon="information-circle" :text="$current->reception_notes" />@endif

                @can('inventory.update')
                    @if ($current->status->value === 'requested')
                        <div class="grid gap-3 border-t border-zinc-200 pt-4 md:grid-cols-2 dark:border-zinc-700">
                            <flux:input wire:model="carrierName" label="Convoyeur (personne qui transporte)" icon="user" />
                            <div class="flex items-end"><flux:button variant="primary" icon="truck" wire:click="ship" class="w-full">Expédier (sortie de stock)</flux:button></div>
                            <flux:input wire:model="cancelReason" label="Motif d’annulation" />
                            <div class="flex items-end"><flux:button variant="danger" icon="x-mark" wire:click="cancel" class="w-full">Annuler la demande</flux:button></div>
                        </div>
                    @elseif ($current->status->value === 'in_transit')
                        <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:text size="sm">Saisissez les quantités réellement reçues ; tout écart reste tracé sur le transfert.</flux:text>
                            <flux:textarea wire:model="receptionNotes" label="Observations de réception" rows="2" />
                            <flux:button variant="primary" icon="inbox-arrow-down" wire:click="receive" class="w-full">Réceptionner (entrée en stock)</flux:button>
                        </div>
                    @endif
                @endcan
            </div>
        @endif
    </flux:modal>
</div>
