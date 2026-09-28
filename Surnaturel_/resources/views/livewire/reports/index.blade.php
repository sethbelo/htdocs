<div class="space-y-6">
    <x-page-header title="Rapports" subtitle="Filtrez par période, siège, PDV, entrepôt, utilisateur, client, produit ou statut ; exportez en PDF ou Excel.">
        <x-slot:actions>
            @can('reports.export')
                <flux:button size="sm" icon="document-arrow-down" wire:click="exportPdf">PDF</flux:button>
                <flux:button size="sm" icon="table-cells" wire:click="exportExcel">Excel</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-4">
        <div class="panel p-2">
            @foreach ($reports as $key => $definition)
                <button wire:click="$set('report', '{{ $key }}')" wire:key="r-{{ $key }}" @class(['block w-full rounded-lg px-3 py-2 text-left text-sm', 'bg-brand-50 font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $report === $key, 'hover:bg-zinc-50 dark:hover:bg-zinc-800' => $report !== $key])>{{ $definition['label'] }}</button>
            @endforeach
        </div>

        <div class="space-y-4 xl:col-span-3">
            <div class="panel grid gap-3 p-4 md:grid-cols-3 xl:grid-cols-4">
                <flux:input type="date" wire:model.live="from" label="Du" />
                <flux:input type="date" wire:model.live="to" label="Au" />
                <flux:select wire:model.live="siteId" label="Siège"><flux:select.option value="">Tous</flux:select.option>@foreach ($sites as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                @if (in_array($report, ['revenue', 'sales']))
                    <flux:select wire:model.live="cashRegisterId" label="PDV / caisse"><flux:select.option value="">Toutes</flux:select.option>@foreach ($registers as $r)<flux:select.option :value="$r->id">{{ $r->code }} — {{ $r->name }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if (in_array($report, ['stock', 'movements']))
                    <flux:select wire:model.live="warehouseId" label="Entrepôt"><flux:select.option value="">Tous</flux:select.option>@foreach ($warehouses as $w)<flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if (in_array($report, ['revenue', 'sales', 'orders', 'production', 'designers', 'sales_reps']))
                    <flux:select wire:model.live="userId" :label="['revenue' => 'Caissier', 'sales' => 'Caissier', 'orders' => 'Commercial', 'sales_reps' => 'Commercial', 'production' => 'Opérateur', 'designers' => 'Graphiste'][$report]"><flux:select.option value="">Tous</flux:select.option>@foreach ($users as $u)<flux:select.option :value="$u->id">{{ $u->name }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if (in_array($report, ['revenue', 'sales', 'orders', 'unpaid']))
                    <flux:select wire:model.live="clientId" label="Client"><flux:select.option value="">Tous</flux:select.option>@foreach ($clients as $c)<flux:select.option :value="$c->id">{{ $c->displayName() }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if (in_array($report, ['sales', 'orders', 'margins', 'stock', 'movements']))
                    <flux:select wire:model.live="productId" label="Produit"><flux:select.option value="">Tous</flux:select.option>@foreach ($products as $p)<flux:select.option :value="$p->id">{{ $p->name }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if (in_array($report, ['orders', 'movements']))
                    <flux:select wire:model.live="status" :label="$report === 'movements' ? 'Type' : 'Statut'"><flux:select.option value="">Tous</flux:select.option>@foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                @endif
            </div>

            <div class="panel p-4" wire:loading.class="opacity-50">
                <div class="mb-2 flex items-center justify-between">
                    <flux:heading>{{ $reports[$report]['label'] ?? '' }}</flux:heading>
                    <flux:text size="sm">{{ $this->data['rows']->count() }} ligne(s)</flux:text>
                </div>
                <flux:table>
                    <flux:table.columns>
                        @foreach ($this->data['headings'] as $heading)<flux:table.column>{{ $heading }}</flux:table.column>@endforeach
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->data['rows']->take(500) as $index => $row)
                            <flux:table.row :key="'row-'.$index">
                                @foreach ($row as $cellIndex => $cell)
                                    <flux:table.cell @class(['text-right tabular-nums' => is_float($cell) || is_int($cell)])>{{ is_float($cell) ? number_format($cell, 2, ',', ' ') : $cell }}</flux:table.cell>
                                @endforeach
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell :colspan="count($this->data['headings'])"><x-empty-state icon="chart-bar" title="Aucune donnée sur la période" /></flux:table.cell></flux:table.row>
                        @endforelse
                        @if ($this->data['totals'])
                            <flux:table.row class="font-semibold">
                                @foreach ($this->data['headings'] as $i => $heading)
                                    <flux:table.cell class="text-right tabular-nums">{{ $i === 0 ? 'Totaux' : (isset($this->data['totals'][$i]) ? number_format($this->data['totals'][$i], 2, ',', ' ') : '') }}</flux:table.cell>
                                @endforeach
                            </flux:table.row>
                        @endif
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>
</div>
