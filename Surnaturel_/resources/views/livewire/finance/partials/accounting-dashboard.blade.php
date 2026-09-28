{{-- Tableau de bord comptable « façon Power BI » : cartes KPI, visuels, matrice comptes × mois --}}
@php
    $money = fn ($v) => \App\Services\Support\Money::format($v);
    $compact = fn ($v) => match (true) {
        abs($v) >= 1000000 => number_format($v / 1000000, 1, ',', ' ').' M',
        abs($v) >= 10000 => number_format($v / 1000, 1, ',', ' ').' k',
        abs($v) >= 100 || fmod((float) $v, 1) == 0.0 => number_format($v, 0, ',', ' '),
        default => number_format($v, 2, ',', ' '),
    };
    $key = md5($from.$to.$siteId.$journalCode);
    $maxCell = collect($a['matrix']['sections'])->flatMap(fn ($s) => collect($s['rows'])->flatMap(fn ($r) => $r['values']))->map(fn ($v) => abs($v))->max() ?: 1;
@endphp

<div class="space-y-6">
    <p class="text-xs text-fg-subtle">Période analysée : du {{ \Illuminate\Support\Carbon::parse($a['period']['from'])->format('d/m/Y') }} au {{ \Illuminate\Support\Carbon::parse($a['period']['to'])->format('d/m/Y') }} · comparée au {{ \Illuminate\Support\Carbon::parse($a['period']['previous_from'])->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($a['period']['previous_to'])->format('d/m/Y') }}</p>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ($a['kpis'] as $name => $kpi)
            @php
                $variation = \App\Services\Accounting\AccountingAnalytics::variation((float) $kpi['value'], $kpi['previous'] === null ? null : (float) $kpi['previous']);
                $positive = $variation !== null && (($variation >= 0) === ($kpi['good'] === 'up'));
            @endphp
            <div @class(['kpi-card relative overflow-hidden p-4', 'md:col-span-1'])>
                <span @class(['absolute inset-x-0 top-0 h-1', 'bg-[#53BDC5]' => in_array($name, ['revenue', 'treasury']), 'bg-[#DB0D17]' => in_array($name, ['expenses', 'payables', 'vat']), 'bg-[#134A63] dark:bg-white' => in_array($name, ['result', 'margin']), 'bg-[#FFCE34]' => $name === 'receivables'])></span>
                <p class="kpi-label">{{ $kpi['label'] }}</p>
                <p class="kpi-value tabular-nums {{ $name === 'result' && $kpi['value'] < 0 ? 'text-carmin-600' : '' }}">{{ ($kpi['percent'] ?? false) ? number_format($kpi['value'], 1, ',', ' ').' %' : $money($kpi['value']) }}</p>
                <p class="mt-1.5 flex items-center gap-1.5 text-xs text-fg-subtle">
                    @if ($variation !== null)
                        <span @class(['inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-semibold', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $positive, 'bg-carmin-50 text-carmin-700 dark:bg-carmin-500/10 dark:text-carmin-300' => ! $positive])>
                            <flux:icon :icon="$variation >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" class="size-3.5" />{{ $variation >= 0 ? '+' : '' }}{{ number_format($variation, 1, ',', ' ') }} %
                        </span>
                        <span>vs période préc.</span>
                    @elseif ($kpi['previous'] === null)
                        <span>Solde à la date de fin</span>
                    @else
                        <span>Pas de base de comparaison</span>
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel p-4 xl:col-span-2">
            <div class="flex items-center justify-between"><flux:heading>Produits, charges et résultat par mois</flux:heading><span class="text-xs text-fg-subtle">{{ count($a['monthly']) }} mois</span></div>
            <div class="mt-3 h-80" wire:key="trend-{{ $key }}" x-data="chart(@js($charts['trend']))"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="panel p-4">
            <flux:heading>Répartition des charges</flux:heading>
            @if ($a['expensesByAccount']->isNotEmpty())
                <div class="mt-3 h-80" wire:key="exp-{{ $key }}" x-data="chart(@js($charts['expenses']))"><canvas x-ref="canvas"></canvas></div>
            @else
                <x-empty-state icon="chart-pie" title="Aucune charge sur la période" />
            @endif
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel p-4">
            <flux:heading>Trésorerie fin de mois</flux:heading>
            <div class="mt-3 h-60" wire:key="tre-{{ $key }}" x-data="chart(@js($charts['treasury']))"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="panel p-4">
            <flux:heading>Produits par compte</flux:heading>
            @if ($a['revenueByAccount']->isNotEmpty())
                <div class="mt-3 h-60" wire:key="rev-{{ $key }}" x-data="chart(@js($charts['revenue']))"><canvas x-ref="canvas"></canvas></div>
            @else
                <x-empty-state icon="chart-bar" title="Aucun produit sur la période" />
            @endif
        </div>
        <div class="panel p-4">
            <flux:heading>Performance par siège</flux:heading>
            @if ($a['bySite']->isNotEmpty())
                <div class="mt-3 h-60" wire:key="sit-{{ $key }}" x-data="chart(@js($charts['sites']))"><canvas x-ref="canvas"></canvas></div>
            @else
                <x-empty-state icon="building-office-2" title="Aucune donnée" />
            @endif
        </div>
    </div>

    <div class="panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-4">
            <div>
                <flux:heading>Matrice comptes de gestion × mois</flux:heading>
                <flux:text class="text-xs">Intensité de couleur proportionnelle au montant · produits en cyan, charges en magenta.</flux:text>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-[13px]">
                <thead>
                    <tr class="bg-zinc-900 text-left text-[11px] uppercase tracking-wide text-white dark:bg-white/10">
                        <th class="sticky left-0 z-10 bg-zinc-900 px-3 py-2.5 dark:bg-zinc-800">Compte</th>
                        @foreach ($a['matrix']['columns'] as $column)<th class="px-3 py-2.5 text-right">{{ $column }}</th>@endforeach
                        <th class="px-3 py-2.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($a['matrix']['sections'] as $index => $section)
                        <tr class="bg-zinc-50 dark:bg-white/4"><td colspan="{{ count($a['matrix']['columns']) + 2 }}" class="px-3 py-2 text-[11px] font-semibold tracking-wide text-fg-muted uppercase">{{ $section['title'] }}</td></tr>
                        @forelse ($section['rows'] as $row)
                            <tr class="border-b border-line">
                                <td class="sticky left-0 z-10 bg-surface px-3 py-2"><span class="font-mono text-xs text-fg-subtle">{{ $row['code'] }}</span> {{ $row['name'] }}</td>
                                @foreach ($row['values'] as $value)
                                    @php($intensity = $value ? min(.85, .08 + abs($value) / $maxCell * .75) : 0)
                                    <td class="px-3 py-2 text-right tabular-nums" style="{{ $value ? 'background: rgba('.($index === 0 ? '0,166,230' : '230,0,126').','.round($intensity * .35, 3).')' : '' }}">{{ $value ? $compact($value) : '·' }}</td>
                                @endforeach
                                <td class="px-3 py-2 text-right font-semibold tabular-nums">{{ $compact($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($a['matrix']['columns']) + 2 }}" class="px-3 py-3 text-center text-fg-subtle">Aucun mouvement.</td></tr>
                        @endforelse
                        <tr class="border-b-2 border-line-strong font-semibold">
                            <td class="sticky left-0 z-10 bg-surface px-3 py-2">Total {{ mb_strtolower($section['title']) }}</td>
                            @foreach ($section['totals'] as $total)<td class="px-3 py-2 text-right tabular-nums">{{ $compact($total) }}</td>@endforeach
                            <td class="px-3 py-2 text-right tabular-nums">{{ $compact($section['total']) }}</td>
                        </tr>
                    @endforeach
                    @php($result = array_map(fn ($i) => ($a['matrix']['sections'][0]['totals'][$i] ?? 0) - ($a['matrix']['sections'][1]['totals'][$i] ?? 0), array_keys($a['matrix']['columns'])))
                    <tr class="bg-zinc-900 font-semibold text-white dark:bg-white/10">
                        <td class="sticky left-0 z-10 bg-zinc-900 px-3 py-2.5 dark:bg-zinc-800">RÉSULTAT</td>
                        @foreach ($result as $value)<td @class(['px-3 py-2.5 text-right tabular-nums', 'text-carmin-300' => $value < 0])>{{ $compact($value) }}</td>@endforeach
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $compact(array_sum($result)) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="panel p-4">
            <flux:heading>Top des charges</flux:heading>
            <ul class="mt-3 space-y-2.5 text-sm">
                @forelse ($a['expensesByAccount']->take(8) as $row)
                    @php($share = $a['kpis']['expenses']['value'] > 0 ? $row['amount'] / $a['kpis']['expenses']['value'] * 100 : 0)
                    <li>
                        <div class="flex justify-between gap-3"><span class="truncate"><span class="font-mono text-xs text-fg-subtle">{{ $row['code'] }}</span> {{ $row['name'] }}</span><span class="shrink-0 font-semibold tabular-nums">{{ $money($row['amount']) }} <span class="text-xs font-normal text-fg-subtle">{{ number_format($share, 1, ',', ' ') }} %</span></span></div>
                        <div class="mt-1 h-1.5 rounded-full bg-zinc-100 dark:bg-white/8"><div class="h-full rounded-full bg-[#DB0D17]" style="width: {{ min(100, $share) }}%"></div></div>
                    </li>
                @empty
                    <li class="text-fg-subtle">Aucune charge sur la période.</li>
                @endforelse
            </ul>
        </div>
        <div class="panel p-4">
            <flux:heading>Activité des journaux</flux:heading>
            <table class="mt-3 w-full text-sm">
                <thead><tr class="text-left text-[11px] text-fg-subtle uppercase"><th class="pb-2">Journal</th><th class="pb-2 text-right">Écritures</th><th class="pb-2 text-right">Montant</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($a['byJournal'] as $row)
                        <tr><td class="py-2"><flux:badge size="sm">{{ $row['code'] }}</flux:badge> {{ $row['name'] }}</td><td class="py-2 text-right tabular-nums">{{ $row['entries'] }}</td><td class="py-2 text-right font-semibold tabular-nums">{{ $money($row['amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-center text-fg-subtle">Aucune écriture.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
