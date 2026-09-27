<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="lg">Catalogue des catégories</flux:heading>
            <flux:subheading>Organisez les produits et leur ordre d’affichage.</flux:subheading>
        </div>
        @can('categories.create')
            <flux:button variant="primary" icon="plus" href="{{ route('admin.categories.create') }}" wire:navigate>Nouvelle catégorie</flux:button>
        @endcan
    </div>

    <flux:card class="max-w-xl">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher une catégorie…" clearable />
    </flux:card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse ($categories as $category)
            <flux:card wire:key="category-{{ $category->id }}" class="flex min-h-36 flex-col justify-between">
                <div class="flex items-start gap-3">
                    <div class="grid size-10 shrink-0 place-items-center rounded-xl" style="background-color: {{ $category->color }}20; color: {{ $category->color }}">
                        <flux:icon.tag class="size-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading class="truncate">{{ $category->name }}</flux:heading>
                        <flux:text size="sm">{{ $category->products_count }} produit(s)</flux:text>
                    </div>
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" square aria-label="Actions" />
                        <flux:menu>
                            @can('categories.edit')
                                <flux:menu.item icon="pencil-square" href="{{ route('admin.categories.edit', $category->id) }}" wire:navigate>Modifier</flux:menu.item>
                                <flux:menu.item icon="power" wire:click="toggleStatus('{{ $category->id }}')">{{ $category->is_active ? 'Désactiver' : 'Activer' }}</flux:menu.item>
                            @endcan
                            @can('categories.delete')
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="requestDelete('{{ $category->id }}')">Supprimer</flux:menu.item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div class="mt-5 flex items-center justify-between">
                    <flux:badge size="sm" :color="$category->is_active ? 'green' : 'zinc'">{{ $category->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                    <flux:text size="sm">Ordre {{ $category->sort_order }}</flux:text>
                </div>
            </flux:card>
        @empty
            <flux:card class="col-span-full py-14 text-center">
                <flux:icon.tag class="mx-auto mb-3 size-10 text-zinc-300" />
                <flux:heading>Aucune catégorie</flux:heading>
                <flux:subheading>Créez votre première catégorie pour structurer le catalogue.</flux:subheading>
            </flux:card>
        @endforelse
    </div>

    <flux:pagination :paginator="$categories" scroll-to />

    <flux:modal wire:model.self="showDeleteModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div class="flex gap-4"><div class="yeboox-icon-tile" style="--tone:#ef4444"><flux:icon.trash class="size-5" /></div><div><flux:heading size="lg">Supprimer la catégorie ?</flux:heading><flux:text class="mt-1">« {{ $deleteName }} » sera supprimée définitivement.</flux:text></div></div>
            @error('delete')<x-dismissible-alert variant="danger" icon="exclamation-triangle">{{ $message }}</x-dismissible-alert>@enderror
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button wire:click="confirmDelete" variant="danger" icon="trash">Confirmer la suppression</flux:button></div>
        </div>
    </flux:modal>
</div>
