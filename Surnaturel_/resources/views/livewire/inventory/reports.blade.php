@php
    $data = $this->data;
    $statusColumn = $data['statusColumn'] ?? null;
    $tones = ['Rupture' => 'red', 'Sous le seuil' => 'amber', 'Surstock' => 'sky', 'Normal' => 'emerald'];
    $kpiTones = ['red' => 'red', 'amber' => 'amber', 'green' => 'emerald'];
    $fmt = fn ($cell) => is_float($cell) ? number_format($cell, fmod($cell, 1) == 0.0 ? 0 : 2, ',', ' ') : $cell;
@endphp
<div class="space-y-6">
    <x-page-header title="Rapports d’entrepôt" eyebrow="Logistique" subtitle="Des rapports clairs pour piloter les stocks : valeur, mouvements, ruptures, dormants, personnel et transferts.">
        <x-slot:actions>
            <flux:button size="sm" icon="signal" :href="route('inventory.report')" wire:navigate>Temps réel</flux:button>
            @can('inventory.export')
                <flux:button size="sm" icon="document-arrow-down" wire:click="exportPdf">PDF signé</flux:button>
                <flux:button size="sm" icon="table-cells" wire:click="exportExcel">Excel</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
        @foreach ($reports as $key => $definition)
            <button type="button" wire:click="$set('report', '{{ $key }}')" wire:key="rep-{{ $key }}" @class(['flex items-center gap-2.5 rounded-xl border p-3 text-left text-sm font-semibold transition', 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20 dark:bg-white/8' => $report === $key, 'border-line bg-surface hover:border-line-strong' => $report !== $key])>
                <flux:icon :icon="$definition['icon']" variant="mini" @class(['shrink-0', 'text-brand-600 dark:text-cyan2-300' => $report === $key, 'text-fg-subtle' => $report !== $key]) />
                <span class="leading-tight">{{ $definition['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="panel flex flex-wrap items-end gap-3 p-3">
        <div>
            <span class="mb-1 block text-xs font-medium text-fg-subtle">Période</span>
            <div class="segmented">
                @foreach (['today' => 'Aujourd’hui', 'week' => 'Semaine', 'month' => 'Mois', 'quarter' => 'Trimestre', 'year' => 'Année'] as $key => $label)
                    <button type="button" wire:click="applyPreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <flux:input type="date" wire:model.live="from" size="sm" label="Du" class="max-w-40" />
        <flux:input type="date" wire:model.live="to" size="sm" label="Au" class="max-w-40" />
        <flux:select wire:model.live="warehouseId" size="sm" label="Entrepôt" class="max-w-56">
            <flux:select.option value="">Tous mes entrepôts</flux:select.option>
            @foreach ($warehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="categoryId" size="sm" label="Catégorie" class="max-w-52">
            <flux:select.option value="">Toutes les catégories</flux:select.option>
            @foreach ($categories as $category)<flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>@endforeach
        </flux:select>
    </div>

    <div class="panel border-l-4 border-l-[#DB0D17] p-4">
        <flux:heading size="lg">{{ $data['title'] }}</flux:heading>
        <flux:text class="mt-1">{{ $data['description'] }}</flux:text>
    </div>

    @if ($data['kpis'])
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($data['kpis'] as $kpi)
                <x-kpi :label="$kpi['label']" :value="$kpi['value']" :color="$kpiTones[$kpi['tone'] ?? ''] ?? 'indigo'" />
            @endforeach
        </div>
    @endif

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-sm">
                <thead>
                    <tr class="bg-zinc-900 text-left text-[11px] uppercase tracking-wide text-white dark:bg-white/10">
                        @foreach ($data['headings'] as $index => $heading)
                            <th @class(['px-3 py-2.5', 'text-right' => collect($data['rows'])->contains(fn ($r) => is_int($r[$index] ?? null) || is_float($r[$index] ?? null))])>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($data['rows'] as $rowIndex => $row)
                        @php($isTotal = in_array($rowIndex, $data['totals'], true))
                        <tr @class(['bg-zinc-50 font-semibold dark:bg-white/4' => $isTotal, 'hover:bg-zinc-50/60 dark:hover:bg-white/3' => ! $isTotal])>
                            @foreach ($row as $index => $cell)
                                <td @class(['px-3 py-2', 'text-right tabular-nums' => is_int($cell) || is_float($cell), 'text-carmin-600' => (is_float($cell) || is_int($cell)) && $cell < 0 && ! $isTotal])>
                                    @if ($index === $statusColumn && filled($cell))
                                        <flux:badge size="sm" :color="$tones[$cell] ?? 'zinc'">{{ $cell }}</flux:badge>
                                    @else
                                        {{ $fmt($cell) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($data['headings']) }}"><x-empty-state icon="archive-box" title="Aucune donnée pour ces critères" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
