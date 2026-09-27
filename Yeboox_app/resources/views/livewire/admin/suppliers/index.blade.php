<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Fournisseurs</h1>
            <p class="text-sm text-slate-500 mt-1">Gérez vos fournisseurs et partenaires</p>
        </div>
        @can('suppliers.create')
        <flux:button variant="primary" icon="plus" href="{{ route('admin.suppliers.create') }}" wire:navigate>Nouveau fournisseur</flux:button>
        @endcan
    </div>

    @if(session('success'))
        <x-dismissible-alert variant="success" icon="check-circle" class="mb-4">{{ session('success') }}</x-dismissible-alert>
    @endif

    <flux:card class="mb-4"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher un fournisseur…" clearable /></flux:card>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
                <tr>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Fournisseur</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase px-4 py-3">Contact</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Commandes</th>
                    <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Délai paiement</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase px-4 py-3">Statut</th>
                    <th class="text-right text-xs font-semibold text-slate-500 uppercase px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" wire:key="sup-{{ $supplier->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-teal-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                    {{ $supplier->initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $supplier->name }}</p>
                                    @if($supplier->contact_name)
                                        <p class="text-xs text-slate-400">{{ $supplier->contact_name }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $supplier->phone ?? '—' }}</p>
                            @if($supplier->email)
                                <p class="text-xs text-slate-400">{{ $supplier->email }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-slate-500">
                            {{ $supplier->purchase_orders_count }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-500">
                            {{ $supplier->payment_terms_days > 0 ? $supplier->payment_terms_days . ' jours' : 'Comptant' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-500' }}">
                                {{ $supplier->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <flux:dropdown position="bottom" align="end"><flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" square/><flux:menu>@can('suppliers.edit')<flux:menu.item icon="pencil-square" href="{{ route('admin.suppliers.edit', $supplier) }}" wire:navigate>Modifier</flux:menu.item><flux:menu.item icon="power" wire:click="toggleStatus('{{ $supplier->id }}')">{{ $supplier->is_active ? 'Désactiver' : 'Activer' }}</flux:menu.item>@endcan @can('suppliers.delete')<flux:menu.separator/><flux:menu.item variant="danger" icon="trash" wire:click="requestDelete('{{ $supplier->id }}')">Supprimer</flux:menu.item>@endcan</flux:menu></flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Aucun fournisseur</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($suppliers->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700"><flux:pagination :paginator="$suppliers" scroll-to /></div>
        @endif
    </div>
    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]"><div class="space-y-6"><div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5"/></div><div><flux:heading size="lg">Supprimer ce fournisseur ?</flux:heading><flux:text>« {{ $deleteName }} » sera archivé dans l’historique.</flux:text></div></div><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Supprimer</flux:button></div></div></flux:modal>
</div>
