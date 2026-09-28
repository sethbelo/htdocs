<div class="space-y-6">
    <x-page-header title="Stocks & inventaire" subtitle="Niveaux par entrepôt, mouvements historisés, transferts, pertes, casse et inventaires physiques.">
        <x-slot:actions>
            <livewire:import-data type="stock" :key="'import-stock'" />
            @can('inventory.export')<flux:button size="sm" icon="arrow-down-tray" wire:click="export">Excel</flux:button>@endcan
            @can('inventory.create')
                <flux:dropdown align="end">
                    <flux:button size="sm" variant="primary" icon="plus" icon-trailing="chevron-down">Mouvement</flux:button>
                    <flux:menu>
                        <flux:menu.item icon="arrow-down-circle" wire:click="openMove('in')">Entrée</flux:menu.item>
                        <flux:menu.item icon="arrow-up-circle" wire:click="openMove('out')">Sortie (bon de sortie)</flux:menu.item>
                        <flux:menu.item icon="arrows-right-left" wire:click="openMove('transfer')">Transfert</flux:menu.item>
                        <flux:menu.item icon="arrow-uturn-left" wire:click="openMove('return')">Retour</flux:menu.item>
                        <flux:menu.item icon="exclamation-triangle" wire:click="openMove('loss')">Perte</flux:menu.item>
                        <flux:menu.item icon="x-circle" wire:click="openMove('breakage')">Casse</flux:menu.item>
                        <flux:menu.item icon="clipboard-document-check" wire:click="openMove('count')">Inventaire (quantité comptée)</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 md:grid-cols-3">
        <x-kpi label="Valeur du stock (coût)" :value="\App\Services\Support\Money::format($stockValue)" icon="archive-box" />
        <x-kpi label="Entrepôts" :value="$warehouses->count()" icon="building-office-2" color="sky" />
        <x-kpi label="Articles gérés en stock" :value="$products->count()" icon="cube" color="purple" />
    </div>

    <div class="panel p-4 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:button size="sm" :variant="$tab === 'levels' ? 'primary' : 'ghost'" wire:click="$set('tab', 'levels')">Niveaux</flux:button>
            <flux:button size="sm" :variant="$tab === 'movements' ? 'primary' : 'ghost'" wire:click="$set('tab', 'movements')">Mouvements</flux:button>
            <flux:separator vertical />
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" size="sm" placeholder="Article, SKU, code-barres…" class="max-w-xs" />
            <flux:select wire:model.live="warehouseId" size="sm" class="max-w-xs">
                <flux:select.option value="">Tous entrepôts</flux:select.option>
                @foreach ($warehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
            </flux:select>
            @if ($tab === 'levels')
                <flux:switch wire:model.live="lowOnly" label="Stock faible" />
            @else
                <flux:select wire:model.live="movementType" size="sm" class="max-w-xs">
                    <flux:select.option value="">Tous mouvements</flux:select.option>
                    @foreach ($movementTypes as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach
                </flux:select>
            @endif
        </div>

        @if ($tab === 'levels')
            <flux:table :paginate="$levels">
                <flux:table.columns>
                    <flux:table.column>Article</flux:table.column>
                    <flux:table.column>Entrepôt</flux:table.column>
                    <flux:table.column align="end">Quantité</flux:table.column>
                    <flux:table.column align="end">Min / Max</flux:table.column>
                    <flux:table.column align="end">Valeur</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($levels as $level)
                        @php($low = (float) $level->quantity <= (float) $level->product->min_stock)
                        <flux:table.row :key="$level->id">
                            <flux:table.cell><div class="font-medium">{{ $level->product->name }}</div><div class="text-xs text-zinc-500">{{ $level->product->sku }}</div></flux:table.cell>
                            <flux:table.cell>{{ $level->warehouse->name }}</flux:table.cell>
                            <flux:table.cell align="end" @class(['font-semibold', 'text-red-600' => $low])>{{ rtrim(rtrim(number_format((float) $level->quantity, 3, ',', ' '), '0'), ',') }} {{ $level->product->unit?->symbol }}</flux:table.cell>
                            <flux:table.cell align="end" class="text-zinc-500">{{ (float) $level->product->min_stock }} / {{ $level->product->max_stock !== null ? (float) $level->product->max_stock : '—' }}</flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="(float) $level->quantity * (float) $level->product->cost_price" /></flux:table.cell>
                            <flux:table.cell>@can('inventory.create')<flux:button size="xs" variant="ghost" icon="plus-circle" wire:click="openMove('in', {{ $level->product_id }}, {{ $level->warehouse_id }})" />@endcan</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:table :paginate="$movements">
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>N°</flux:table.column>
                    <flux:table.column>Article</flux:table.column>
                    <flux:table.column>Entrepôt</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column align="end">Quantité</flux:table.column>
                    <flux:table.column align="end">Solde</flux:table.column>
                    <flux:table.column>Pièce</flux:table.column>
                    <flux:table.column>Intervenant</flux:table.column>
                    <flux:table.column>Saisi par</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($movements as $movement)
                        <flux:table.row :key="$movement->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $movement->moved_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $movement->number }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->product->name }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->warehouse->name }}@if ($movement->relatedWarehouse)<span class="text-xs text-zinc-500"> ↔ {{ $movement->relatedWarehouse->name }}</span>@endif</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$movement->type->color()">{{ $movement->type->label() }}</flux:badge></flux:table.cell>
                            <flux:table.cell align="end" @class(['font-medium', 'text-emerald-600' => $movement->quantity > 0, 'text-red-600' => $movement->quantity < 0])>{{ $movement->quantity > 0 ? '+' : '' }}{{ (float) $movement->quantity }}</flux:table.cell>
                            <flux:table.cell align="end">{{ (float) $movement->balance_after }}</flux:table.cell>
                            <flux:table.cell class="text-xs">{{ $movement->source?->number ?? '—' }}<div class="max-w-48 truncate text-zinc-500">{{ $movement->reason }}</div></flux:table.cell>
                            <flux:table.cell class="text-sm">{{ $movement->performerName() ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="text-sm">{{ $movement->user?->name }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="move" class="w-full max-w-lg">
        <form wire:submit="saveMove" class="space-y-4">
            <flux:heading size="lg">{{ ['in' => 'Entrée de stock', 'out' => 'Bon de sortie', 'transfer' => 'Bon de transfert', 'loss' => 'Déclaration de perte', 'breakage' => 'Déclaration de casse', 'return' => 'Retour en stock', 'count' => 'Inventaire physique'][$move['operation']] ?? 'Mouvement' }}</flux:heading>
            <flux:select wire:model="move.product_id" label="Article">
                <flux:select.option value="">Choisir…</flux:select.option>
                @foreach ($products as $product)<flux:select.option :value="$product->id">{{ $product->name }} ({{ $product->sku }})</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model="move.warehouse_id" :label="$move['operation'] === 'transfer' ? 'Entrepôt source' : 'Entrepôt'">
                <flux:select.option value="">Choisir…</flux:select.option>
                @foreach ($warehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
            </flux:select>
            @if ($move['operation'] === 'transfer')
                <flux:select wire:model="move.to_warehouse_id" label="Entrepôt destination">
                    <flux:select.option value="">Choisir…</flux:select.option>
                    @foreach ($allWarehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
                </flux:select>
            @endif
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="move.quantity" type="number" step="any" :label="$move['operation'] === 'count' ? 'Quantité comptée' : 'Quantité'" />
                @if ($move['operation'] === 'in')<flux:input wire:model="move.unit_cost" type="number" step="0.01" label="Coût unitaire" />@endif
            </div>
            <flux:input wire:model="move.reason" label="Motif / référence" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
