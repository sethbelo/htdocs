<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestion des stocks</h1>
            <p class="text-sm text-slate-500 mt-1">Suivi des niveaux de stock par entrepôt</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card text-center">
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $totalLow }}</p>
            <p class="text-xs text-slate-500 mt-1">Stock faible</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $totalOut }}</p>
            <p class="text-xs text-slate-500 mt-1">En rupture</p>
        </div>
        <div class="stat-card text-center">
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stocks->total() - $totalLow - $totalOut }}</p>
            <p class="text-xs text-slate-500 mt-1">Stock normal</p>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <flux:input class="flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher un produit…" clearable />
            <flux:select wire:model.live="warehouseFilter" class="sm:w-52">
                <flux:select.option value="">Tous les entrepôts</flux:select.option>
                @foreach($warehouses as $w)
                    <flux:select.option value="{{ $w->id }}">{{ $w->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="stockFilter" class="sm:w-44">
                <flux:select.option value="">Tous</flux:select.option>
                <flux:select.option value="low">Stock faible</flux:select.option>
                <flux:select.option value="out">En rupture</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    <!-- Stock Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
                <tr>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Produit</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Entrepôt</th>
                    <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Quantité</th>
                    <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Seuil alerte</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @forelse($stocks as $stock)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" wire:key="stk-{{ $stock->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                    <x-heroicon-o-cube class="w-4 h-4 text-slate-400"/>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $stock->product->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $stock->product->sku }} · {{ $stock->product->category->name ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $stock->warehouse->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-semibold {{ $stock->isOutOfStock() ? 'text-red-600' : ($stock->isLowStock() ? 'text-amber-600' : 'text-slate-900 dark:text-white') }}">
                                {{ number_format($stock->quantity, 0, ',', '.') }}
                            </span>
                            @if($stock->product->unit)
                                <span class="text-xs text-slate-400">{{ $stock->product->unit->abbreviation }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-400">{{ number_format($stock->alert_quantity, 0) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($stock->isOutOfStock())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Rupture
                                </span>
                            @elseif($stock->isLowStock())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Faible
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> OK
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">Aucun stock</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($stocks->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700"><flux:pagination :paginator="$stocks" scroll-to /></div>
        @endif
    </div>
</div>
