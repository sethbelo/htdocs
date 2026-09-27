<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Commandes</h1>
            <p class="text-sm text-slate-500 mt-1">Historique et suivi des commandes</p>
        </div>
        <flux:button variant="primary" icon="computer-desktop" href="{{ route('pos.terminal') }}" target="_blank">Ouvrir le PDV</flux:button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500">Aujourd'hui</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['today_count'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <x-heroicon-o-shopping-bag class="w-5 h-5 text-blue-600 dark:text-blue-400"/>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500">CA du jour</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['today_revenue'], 0, ',', '.') }}</p>
                    <p class="text-[10px] text-slate-400">FC</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <x-heroicon-o-banknotes class="w-5 h-5 text-emerald-600 dark:text-emerald-400"/>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500">En cours</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $stats['active'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <x-heroicon-o-fire class="w-5 h-5 text-amber-600 dark:text-amber-400"/>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500">Ticket moyen</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($stats['avg_ticket'], 0, ',', '.') }}</p>
                    <p class="text-[10px] text-slate-400">FC</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <x-heroicon-o-receipt-percent class="w-5 h-5 text-purple-600 dark:text-purple-400"/>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <flux:card class="mb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <flux:input class="flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N° de commande…" clearable />
            <flux:input wire:model.live="dateFilter" type="date" />
            <flux:select wire:model.live="statusFilter" class="sm:w-48">
                <flux:select.option value="">Tous les statuts</flux:select.option>
                <flux:select.option value="draft">Brouillon</flux:select.option>
                <flux:select.option value="confirmed">Confirmée</flux:select.option>
                <flux:select.option value="preparing">En préparation</flux:select.option>
                <flux:select.option value="ready">Prête</flux:select.option>
                <flux:select.option value="served">Servie</flux:select.option>
                <flux:select.option value="completed">Terminée</flux:select.option>
                <flux:select.option value="cancelled">Annulée</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="typeFilter" class="sm:w-40">
                <flux:select.option value="">Tous les types</flux:select.option>
                <flux:select.option value="dine_in">Sur place</flux:select.option>
                <flux:select.option value="takeaway">À emporter</flux:select.option>
                <flux:select.option value="delivery">Livraison</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">N° Commande</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Type</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Table / Client</th>
                        <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Articles</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Total</th>
                        <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Statut</th>
                        <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Paiement</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Date</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" wire:key="ord-{{ $order->id }}">
                            <td class="px-4 py-3">
                                <p class="text-sm font-mono font-semibold text-primary-600">{{ $order->order_number }}</p>
                                <p class="text-[10px] text-slate-400">{{ $order->user?->name }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $order->type->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($order->table)
                                    <p class="text-xs font-medium text-slate-900 dark:text-white">{{ $order->table->name }}</p>
                                @endif
                                @if($order->customer)
                                    <p class="text-xs text-slate-400">{{ $order->customer->full_name }}</p>
                                @endif
                                @if(!$order->table && !$order->customer)
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-slate-500">{{ $order->items->count() }}</td>
                            <td class="px-4 py-3 text-right">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($order->total, 0, ',', '.') }} FC</p>
                                @if($order->discount_amount > 0)
                                    <p class="text-[10px] text-red-500">-{{ number_format($order->discount_amount, 0, ',', '.') }} remise</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                                        'confirmed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        'preparing' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                        'ready' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'served' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$order->status->value] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($order->is_paid)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-emerald-600">
                                        <x-heroicon-o-check-circle class="w-3.5 h-3.5"/> Payé
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-amber-600">
                                        <x-heroicon-o-clock class="w-3.5 h-3.5"/> En attente
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                {{ $order->created_at->format('d/m H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right"><flux:dropdown position="bottom" align="end"><flux:button size="sm" variant="subtle" icon="ellipsis-horizontal" square/><flux:menu><flux:menu.item icon="eye" wire:click="viewOrder('{{ $order->id }}')">Voir les détails</flux:menu.item><flux:menu.item icon="printer" href="{{ route('admin.receipt.stream', $order) }}" target="_blank">Ouvrir le PDF</flux:menu.item>@if(!in_array($order->status->value, ['completed', 'cancelled'])) @can('orders.cancel')<flux:menu.separator/><flux:menu.item variant="danger" icon="x-circle" wire:click="requestCancel('{{ $order->id }}')">Annuler</flux:menu.item>@endcan @endif</flux:menu></flux:dropdown></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <x-heroicon-o-shopping-bag class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3"/>
                                <p class="text-sm font-medium text-slate-500">Aucune commande trouvée</p>
                                <p class="text-xs text-slate-400 mt-1">Les commandes passées via le PDV apparaîtront ici</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700"><flux:pagination :paginator="$orders" scroll-to /></div>
        @endif
    </div>

    <flux:modal wire:model.self="showDetailsModal" class="md:w-[48rem]">
        @if($selectedOrder)<div class="space-y-6"><div class="flex items-start justify-between gap-4"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#2563eb"><flux:icon.shopping-bag class="size-5"/></div><div><flux:heading size="lg">{{ $selectedOrder->order_number }}</flux:heading><flux:text>{{ $selectedOrder->created_at->format('d/m/Y à H:i') }} · {{ $selectedOrder->user?->name }}</flux:text></div></div><flux:badge :color="$selectedOrder->status->color()">{{ $selectedOrder->status->label() }}</flux:badge></div><div class="grid gap-3 sm:grid-cols-3"><div class="yeboox-card-tone p-4" style="--tone:#2563eb"><flux:text size="sm">Client</flux:text><p class="mt-1 font-medium">{{ $selectedOrder->customer?->full_name ?? 'Client comptoir' }}</p></div><div class="yeboox-card-tone p-4" style="--tone:#8b5cf6"><flux:text size="sm">Table</flux:text><p class="mt-1 font-medium">{{ $selectedOrder->table?->name ?? 'Sans table' }}</p></div><div class="yeboox-card-tone p-4" style="--tone:#10b981"><flux:text size="sm">Total</flux:text><p class="mt-1 text-lg font-semibold">{{ number_format($selectedOrder->total, 0, ',', '.') }} FC</p></div></div><div class="rounded-xl border border-zinc-200 dark:border-zinc-700"><div class="divide-y divide-zinc-200 dark:divide-zinc-700">@foreach($selectedOrder->items as $item)<div class="flex justify-between gap-4 p-3"><div><p class="font-medium">{{ $item->name }}</p><flux:text size="sm">{{ number_format($item->quantity, 0) }} × {{ number_format($item->unit_price, 0, ',', '.') }} FC</flux:text></div><p class="font-semibold">{{ number_format($item->total, 0, ',', '.') }} FC</p></div>@endforeach</div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDetailsModal', false)">Fermer</flux:button><flux:button href="{{ route('admin.receipt.stream', $selectedOrder) }}" target="_blank" variant="primary" icon="printer">Ticket PDF</flux:button></div></div>@endif
    </flux:modal>

    <flux:modal wire:model.self="showCancelModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.x-circle class="size-5"/></div><div><flux:heading size="lg">Annuler la commande ?</flux:heading><flux:text>Cette action libérera la table associée et marquera la commande comme annulée.</flux:text></div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showCancelModal', false)">Retour</flux:button><flux:button wire:click="confirmCancel" variant="danger" icon="x-circle">Confirmer l’annulation</flux:button></div></div></flux:modal>
</div>
