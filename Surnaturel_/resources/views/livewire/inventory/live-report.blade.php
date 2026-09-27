@php($r = $this->report)
<div class="space-y-6" @if ($live) wire:poll.10s @endif>
    <x-page-header title="Rapport d’entrepôt en temps réel" eyebrow="Logistique" subtitle="Toutes les entrées et sorties, qui les a enregistrées et qui les a effectuées. Export du rapport journalier signé.">
        <x-slot:actions>
            @can('inventory.export')
                <flux:button size="sm" icon="document-arrow-down" wire:click="exportPdf">Rapport journalier PDF</flux:button>
                <flux:button size="sm" icon="table-cells" wire:click="exportExcel">Excel</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="panel flex flex-wrap items-end gap-3 p-4">
        <flux:select wire:model.live="warehouseId" label="Entrepôt" class="min-w-56">
            <flux:select.option value="">Tous mes entrepôts</flux:select.option>
            @foreach ($warehouses as $warehouse)<flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>@endforeach
        </flux:select>
        <div class="flex items-end gap-1">
            <flux:button icon="chevron-left" wire:click="shiftDay(-1)" aria-label="Jour précédent" />
            <flux:input wire:model.live="date" type="date" label="Journée" />
            <flux:button icon="chevron-right" wire:click="shiftDay(1)" aria-label="Jour suivant" />
        </div>
        <div class="segmented">
            @foreach (['' => 'Tout', 'in' => 'Entrées', 'out' => 'Sorties'] as $key => $label)
                <button type="button" wire:click="$set('direction', '{{ $key }}')" aria-pressed="{{ $direction === $key ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <flux:spacer />
        @if ($live)
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                En direct · mis à jour {{ now()->format('H:i:s') }}
            </span>
        @else
            <flux:badge color="zinc" icon="archive-box">Journée archivée</flux:badge>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <x-kpi label="Mouvements" :value="$r['kpis']['count']" icon="arrows-up-down" />
        <x-kpi label="Entrées" :value="$r['kpis']['entries']" icon="arrow-down-tray" color="emerald" :hint="$money($r['kpis']['value_in'])" />
        <x-kpi label="Sorties" :value="$r['kpis']['exits']" icon="arrow-up-tray" color="red" :hint="$money($r['kpis']['value_out'])" />
        <x-kpi label="Qté entrée" :value="number_format($r['kpis']['qty_in'], 0, ',', ' ')" icon="plus-circle" color="sky" />
        <x-kpi label="Qté sortie" :value="number_format($r['kpis']['qty_out'], 0, ',', ' ')" icon="minus-circle" color="amber" />
        <x-kpi label="Articles touchés" :value="$r['kpis']['products']" icon="cube" color="purple" :hint="$r['kpis']['people'].' intervenant(s)'" />
    </div>

    @php($peak = max(1, collect($r['hourly'])->map(fn ($h) => $h['in'] + $h['out'])->max()))
    <div class="panel p-4">
        <div class="flex items-center justify-between"><flux:heading>Activité heure par heure</flux:heading><span class="flex gap-3 text-xs text-fg-subtle"><span class="flex items-center gap-1"><span class="size-2 rounded-sm bg-emerald-500"></span>Entrées</span><span class="flex items-center gap-1"><span class="size-2 rounded-sm bg-carmin-500"></span>Sorties</span></span></div>
        <div class="mt-4 flex h-28 items-end gap-1">
            @foreach ($r['hourly'] as $hour => $h)
                <div class="flex h-full flex-1 flex-col items-center justify-end gap-px" title="{{ $hour }} h : {{ $h['in'] }} entrée(s), {{ $h['out'] }} sortie(s)">
                    <div class="w-full rounded-t-sm bg-carmin-500/85" style="height: {{ round($h['out'] / $peak * 100) }}%"></div>
                    <div class="w-full bg-emerald-500/85" style="height: {{ round($h['in'] / $peak * 100) }}%"></div>
                    <span class="mt-1 text-[9px] text-fg-subtle">{{ $hour % 3 === 0 ? $hour : '' }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel xl:col-span-2">
            <div class="border-b border-line p-4"><flux:heading>Fil des mouvements</flux:heading></div>
            <ul class="max-h-[640px] divide-y divide-line overflow-y-auto">
                @forelse ($r['movements'] as $m)
                    <li class="flex items-start gap-3 px-4 py-3 text-sm" wire:key="mv-{{ $m->id }}">
                        <span @class(['grid size-9 shrink-0 place-items-center rounded-xl', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' => $m->signed_quantity > 0, 'bg-carmin-50 text-carmin-600 dark:bg-carmin-500/10' => $m->signed_quantity < 0])>
                            <flux:icon :icon="$m->signed_quantity > 0 ? 'arrow-down-tray' : 'arrow-up-tray'" variant="mini" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold">{{ $m->product->name }}</span>
                                <flux:badge size="sm" :color="$m->type->color()">{{ $m->type->label() }}</flux:badge>
                                @if ($ref = $builder->sourceReference($m))<span class="font-mono text-xs text-fg-subtle">{{ $ref }}</span>@endif
                            </div>
                            <div class="mt-0.5 text-xs text-fg-subtle">
                                {{ $m->warehouse->name }}{{ $m->relatedWarehouse ? ' ⇄ '.$m->relatedWarehouse->name : '' }}
                                · enregistré par <span class="font-medium text-fg-muted">{{ $m->user?->name ?? 'Système' }}</span>
                                @if ($performer = $m->performerName()) · effectué par <span class="font-medium text-fg-muted">{{ $performer }}</span>@endif
                                @if ($m->reason) · {{ $m->reason }}@endif
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div @class(['font-semibold tabular-nums', 'text-emerald-600' => $m->signed_quantity > 0, 'text-carmin-600' => $m->signed_quantity < 0])>{{ $m->signed_quantity > 0 ? '+' : '' }}{{ (float) $m->signed_quantity }} {{ $m->product->unit?->symbol }}</div>
                            <div class="text-xs text-fg-subtle">{{ $m->moved_at->format('H:i') }} · stock {{ (float) $m->balance_after }}</div>
                        </div>
                    </li>
                @empty
                    <li><x-empty-state icon="archive-box" title="Aucun mouvement ce jour" text="Les entrées et sorties apparaissent ici dès leur enregistrement." /></li>
                @endforelse
            </ul>
        </div>

        <div class="space-y-6">
            <div class="panel p-4">
                <flux:heading>Intervenants</flux:heading>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($r['byUser'] as $u)
                        <li class="flex items-center gap-3">
                            <span class="avatar size-8 bg-brand-100 text-[11px] text-brand-700 dark:bg-white/10 dark:text-white">{{ mb_strtoupper(mb_substr($u['name'], 0, 2)) }}</span>
                            <span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $u['name'] }}</span><span class="text-xs text-fg-subtle">{{ $u['in'] }} entrée(s) · {{ $u['out'] }} sortie(s)</span></span>
                            <span class="text-xs text-fg-subtle">{{ $u['last']->format('H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-fg-subtle">—</li>
                    @endforelse
                </ul>
            </div>
            <div class="panel p-4">
                <flux:heading>Synthèse par article</flux:heading>
                <table class="mt-3 w-full text-sm">
                    <thead><tr class="text-left text-[11px] text-fg-subtle uppercase"><th class="pb-1">Article</th><th class="pb-1 text-right">Ouv.</th><th class="pb-1 text-right">+</th><th class="pb-1 text-right">−</th><th class="pb-1 text-right">Clôt.</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($r['products'] as $p)
                            <tr><td class="py-1.5 pr-2"><span class="line-clamp-1">{{ $p['product']->name }}</span></td><td class="py-1.5 text-right tabular-nums">{{ $p['opening'] }}</td><td class="py-1.5 text-right text-emerald-600 tabular-nums">{{ $p['in'] ?: '' }}</td><td class="py-1.5 text-right text-carmin-600 tabular-nums">{{ $p['out'] ?: '' }}</td><td class="py-1.5 text-right font-semibold tabular-nums">{{ $p['closing'] }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-center text-fg-subtle">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
