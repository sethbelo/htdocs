<div class="space-y-6">
    <x-page-header title="Entrées & sorties magasin" subtitle="Bons d’entrée et de sortie : article, quantité, personne qui livre ou retire, et magasinier qui enregistre.">
        <x-slot:actions>
            @can('inventory.create')
                <flux:button size="sm" icon="arrow-down-tray" wire:click="create('entry')" class="!bg-emerald-600 !text-white hover:!bg-emerald-700">Bon d’entrée</flux:button>
                <flux:button size="sm" icon="arrow-up-tray" wire:click="create('exit')" variant="danger">Bon de sortie</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-kpi label="Entrées aujourd’hui" :value="$stats['entries_today']" icon="arrow-down-circle" color="emerald" />
        <x-kpi label="Valeur entrée" :value="\App\Services\Support\Money::format($stats['value_in'])" icon="banknotes" color="emerald" />
        <x-kpi label="Sorties aujourd’hui" :value="$stats['exits_today']" icon="arrow-up-circle" color="red" />
        <x-kpi label="Valeur sortie" :value="\App\Services\Support\Money::format($stats['value_out'])" icon="banknotes" color="red" />
    </div>

    <div class="panel space-y-4 p-4">
        <div class="grid gap-3 md:grid-cols-4">
            <flux:select wire:model.live="warehouseId">
                <flux:select.option value="">Tous mes entrepôts</flux:select.option>
                @foreach ($this->warehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model.live="type">
                <flux:select.option value="">Entrées et sorties</flux:select.option>
                <flux:select.option value="entry">Entrées</flux:select.option>
                <flux:select.option value="exit">Sorties</flux:select.option>
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N°, personne, référence…" />
            <flux:input type="date" wire:model.live="from" />
        </div>

        <flux:table :paginate="$operations">
            <flux:table.columns>
                <flux:table.column>Bon</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Entrepôt</flux:table.column>
                <flux:table.column>Motif</flux:table.column>
                <flux:table.column>Personne (livre / retire)</flux:table.column>
                <flux:table.column>Enregistré par</flux:table.column>
                <flux:table.column align="end">Valeur</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($operations as $operation)
                    <flux:table.row :key="$operation->id">
                        <flux:table.cell>
                            <button wire:click="show({{ $operation->id }})" class="font-medium hover:text-indigo-600">{{ $operation->number }}</button>
                            <div><flux:badge size="sm" :color="$operation->isEntry() ? 'emerald' : 'red'">{{ $operation->isEntry() ? 'Entrée' : 'Sortie' }} · {{ $operation->movements_count }} art.</flux:badge></div>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $operation->operated_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $operation->warehouse->name }}</flux:table.cell>
                        <flux:table.cell>{{ $operation->reason->label() }}<div class="text-xs text-zinc-500">{{ $operation->counterpartyLabel() }}</div></flux:table.cell>
                        <flux:table.cell><span class="font-medium">{{ $operation->performed_by_name }}</span><div class="text-xs text-zinc-500">{{ $operation->performed_by_phone }} {{ $operation->reference ? '· réf. '.$operation->reference : '' }}</div></flux:table.cell>
                        <flux:table.cell>{{ $operation->recorder->name }}</flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$operation->total_value" /></flux:table.cell>
                        <flux:table.cell><flux:button size="xs" variant="ghost" icon="printer" :href="route('print.stock-operation', $operation)" target="_blank" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="arrows-up-down" title="Aucun mouvement enregistré" text="Utilisez « Bon d’entrée » ou « Bon de sortie »." /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="operation" class="w-full max-w-3xl">
        @if ($form)
            @php($isEntry = ($form['type'] ?? 'entry') === 'entry')
            <form wire:submit="save" class="space-y-5">
                <div class="flex items-center gap-3">
                    <div @class(['rounded-lg p-2', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' => $isEntry, 'bg-red-50 text-red-600 dark:bg-red-500/10' => ! $isEntry])>
                        <flux:icon :icon="$isEntry ? 'arrow-down-tray' : 'arrow-up-tray'" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $isEntry ? 'Bon d’entrée' : 'Bon de sortie' }}</flux:heading>
                        <flux:text size="sm">Enregistré par {{ auth()->user()->name }}</flux:text>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <flux:select wire:model="form.warehouse_id" label="Entrepôt">
                        @foreach ($this->warehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="form.reason" label="Motif">
                        @foreach ($reasons as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="form.operated_at" type="datetime-local" label="Date et heure" />
                </div>

                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:heading size="sm">{{ $isEntry ? 'Provenance et personne qui livre' : 'Destination et personne qui retire' }}</flux:heading>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <flux:select wire:model.live="form.counterparty_type" :label="$isEntry ? 'Provenance' : 'Remis à'">
                            <flux:select.option value="supplier">Fournisseur</flux:select.option>
                            <flux:select.option value="employee">Employé</flux:select.option>
                            <flux:select.option value="department">Service / département</flux:select.option>
                            <flux:select.option value="other">Autre</flux:select.option>
                        </flux:select>
                        @if ($form['counterparty_type'] === 'supplier')
                            <flux:select wire:model="form.supplier_id" label="Fournisseur" class="md:col-span-2"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($suppliers as $supplier)<flux:select.option :value="$supplier->id">{{ $supplier->name }}</flux:select.option>@endforeach</flux:select>
                        @elseif ($form['counterparty_type'] === 'employee')
                            <flux:select wire:model.live="form.employee_id" label="Employé" class="md:col-span-2"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($employees as $employee)<flux:select.option :value="$employee->id">{{ $employee->first_name }} {{ $employee->last_name }}</flux:select.option>@endforeach</flux:select>
                        @elseif ($form['counterparty_type'] === 'department')
                            <flux:select wire:model="form.department_id" label="Service" class="md:col-span-2"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($departments as $department)<flux:select.option :value="$department->id">{{ $department->name }}</flux:select.option>@endforeach</flux:select>
                        @else
                            <div class="md:col-span-2"></div>
                        @endif
                        <flux:input wire:model="form.performed_by_name" :label="$isEntry ? 'Livré par (nom)' : 'Retiré par (nom)'" icon="user" required />
                        <flux:input wire:model="form.performed_by_phone" label="Téléphone" />
                        <flux:input wire:model="form.reference" :label="$isEntry ? 'Réf. bon de livraison' : 'Réf. demande / bon'" />
                    </div>
                </div>

                <div class="space-y-2">
                    <flux:heading size="sm">Articles</flux:heading>
                    @foreach ($lines as $index => $line)
                        <div class="grid grid-cols-12 gap-2" wire:key="op-line-{{ $index }}">
                            <flux:select wire:model="lines.{{ $index }}.product_id" class="col-span-12 sm:col-span-6">
                                <flux:select.option value="">Article…</flux:select.option>
                                @foreach ($products as $product)<flux:select.option :value="$product->id">{{ $product->name }} ({{ $product->sku }})</flux:select.option>@endforeach
                            </flux:select>
                            <flux:input wire:model="lines.{{ $index }}.quantity" type="number" step="any" placeholder="Quantité" class="col-span-5 sm:col-span-2" />
                            <flux:input wire:model="lines.{{ $index }}.unit_cost" type="number" step="0.01" :placeholder="$isEntry ? 'Coût unitaire' : 'Coût (auto)'" class="col-span-5 sm:col-span-3" />
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeLine({{ $index }})" class="col-span-2 sm:col-span-1" />
                        </div>
                    @endforeach
                    <flux:error name="lines" />
                    <flux:error name="lines.*.product_id" />
                    <flux:error name="lines.*.quantity" />
                    <flux:button type="button" size="sm" icon="plus" wire:click="addLine">Ajouter un article</flux:button>
                </div>

                <flux:textarea wire:model="form.notes" label="Observations" rows="2" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">Enregistrer & imprimer le bon</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    <flux:modal name="detail" class="w-full max-w-2xl">
        @if ($viewing)
            <div class="space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $viewing->typeLabel() }} {{ $viewing->number }}</flux:heading>
                        <flux:text>{{ $viewing->reason->label() }} · {{ $viewing->warehouse->name }} · {{ $viewing->operated_at->format('d/m/Y H:i') }}</flux:text>
                    </div>
                    <flux:button size="sm" icon="printer" :href="route('print.stock-operation', $viewing)" target="_blank">Imprimer</flux:button>
                </div>
                <dl class="grid grid-cols-2 gap-2 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <div><dt class="text-xs text-zinc-500">{{ $viewing->isEntry() ? 'Livré par' : 'Retiré par' }}</dt><dd class="font-medium">{{ $viewing->performed_by_name }} {{ $viewing->performed_by_phone }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Enregistré par</dt><dd class="font-medium">{{ $viewing->recorder->name }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">{{ $viewing->isEntry() ? 'Provenance' : 'Destination' }}</dt><dd>{{ $viewing->counterpartyLabel() }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Référence</dt><dd>{{ $viewing->reference ?? '—' }}</dd></div>
                </dl>
                <flux:table>
                    <flux:table.columns><flux:table.column>Article</flux:table.column><flux:table.column align="end">Quantité</flux:table.column><flux:table.column align="end">Stock après</flux:table.column><flux:table.column align="end">Valeur</flux:table.column></flux:table.columns>
                    <flux:table.rows>
                        @foreach ($viewing->movements as $movement)
                            <flux:table.row :key="$movement->id">
                                <flux:table.cell>{{ $movement->product->name }}</flux:table.cell>
                                <flux:table.cell align="end" @class(['text-emerald-600' => $movement->quantity > 0, 'text-red-600' => $movement->quantity < 0])>{{ (float) $movement->quantity }}</flux:table.cell>
                                <flux:table.cell align="end">{{ (float) $movement->balance_after }}</flux:table.cell>
                                <flux:table.cell align="end"><x-money :amount="abs((float) $movement->quantity) * (float) $movement->unit_cost" /></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                @if ($viewing->notes)<flux:callout icon="information-circle" :text="$viewing->notes" />@endif
            </div>
        @endif
    </flux:modal>
</div>
