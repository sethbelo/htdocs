<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="lg">Catalogue produits</flux:heading>
            <flux:subheading>Prix, coûts, disponibilité et organisation du catalogue.</flux:subheading>
        </div>
        @can('products.create')
            <flux:button variant="primary" icon="plus" href="{{ route('admin.products.create') }}" wire:navigate>Nouveau produit</flux:button>
        @endcan
    </div>

    <flux:card>
        <div class="grid gap-3 lg:grid-cols-[minmax(18rem,1fr)_16rem_12rem]">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, SKU ou code-barres…" clearable />
            <flux:select wire:model.live="categoryFilter" placeholder="Toutes les catégories">
                <flux:select.option value="">Toutes les catégories</flux:select.option>
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="statusFilter" placeholder="Tous les statuts">
                <flux:select.option value="">Tous les statuts</flux:select.option>
                <flux:select.option value="active">Actifs</flux:select.option>
                <flux:select.option value="inactive">Inactifs</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    <flux:card class="p-0!">
        <flux:table :paginate="$products" pagination:scroll-to>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sortBy('name')">Produit</flux:table.column>
                <flux:table.column>Catégorie</flux:table.column>
                <flux:table.column align="end" sortable :sorted="$sortBy === 'selling_price'" :direction="$sortDir" wire:click="sortBy('selling_price')">Prix de vente</flux:table.column>
                <flux:table.column align="end">Coût</flux:table.column>
                <flux:table.column align="end">Marge</flux:table.column>
                <flux:table.column align="center">Statut</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($products as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                @if ($product->image_url)
                                    <flux:avatar size="sm" :src="$product->image_url" :initials="mb_strtoupper(mb_substr($product->name, 0, 2))" />
                                @else
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/40 dark:text-primary-300">
                                        <x-dynamic-component :component="'heroicon-o-'.($product->icon ?: $product->type?->icon() ?: 'cube')" class="size-5" />
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <flux:heading class="truncate">{{ $product->name }}</flux:heading>
                                    <flux:text size="sm">{{ $product->sku ?: 'Sans SKU' }}</flux:text>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->category?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end" variant="strong">{{ number_format($product->selling_price, 0, ',', '.') }} FC</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format($product->cost_price, 0, ',', '.') }} FC</flux:table.cell>
                        <flux:table.cell align="end"><flux:badge size="sm" :color="$product->margin >= 30 ? 'green' : ($product->margin >= 15 ? 'amber' : 'red')">{{ number_format($product->margin, 1, ',', '.') }}%</flux:badge></flux:table.cell>
                        <flux:table.cell align="center"><flux:badge size="sm" :color="$product->is_active ? 'green' : 'zinc'">{{ $product->is_active ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" square aria-label="Actions pour {{ $product->name }}" />
                                <flux:menu>
                                    @can('products.edit')
                                        <flux:menu.item icon="pencil-square" href="{{ route('admin.products.edit', $product->id) }}" wire:navigate>Modifier</flux:menu.item>
                                        <flux:menu.item icon="power" wire:click="toggleStatus('{{ $product->id }}')">{{ $product->is_active ? 'Désactiver' : 'Activer' }}</flux:menu.item>
                                    @endcan
                                    @can('products.delete')
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="requestDelete('{{ $product->id }}')">Supprimer</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-14 text-center">
                            <flux:heading>Aucun produit trouvé</flux:heading>
                            <flux:subheading>Modifiez les filtres ou ajoutez un produit.</flux:subheading>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5" /></div><div><flux:heading size="lg">Supprimer le produit ?</flux:heading><flux:text class="mt-1">« {{ $deleteName }} » ne sera plus disponible dans le catalogue ni en caisse.</flux:text></div></div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Confirmer la suppression</flux:button></div>
        </div>
    </flux:modal>
</div>
