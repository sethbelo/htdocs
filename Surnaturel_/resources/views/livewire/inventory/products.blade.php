<div class="space-y-6">
    <x-page-header title="Articles" subtitle="Services d’impression, papiers, encres, consommables, produits finis et marchandises boutique.">
        <x-slot:actions>
            @can('products.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="create">Nouvel article</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="panel p-4 space-y-4">
        <div class="grid gap-3 md:grid-cols-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, SKU, code-barres, RFID…" class="md:col-span-2" />
            <flux:select wire:model.live="type"><flux:select.option value="">Tous types</flux:select.option>@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="categoryId"><flux:select.option value="">Toutes catégories</flux:select.option>@foreach ($categories as $c)<flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>@endforeach</flux:select>
        </div>
        <flux:table :paginate="$products">
            <flux:table.columns>
                <flux:table.column>SKU</flux:table.column>
                <flux:table.column>Article</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Catégorie</flux:table.column>
                <flux:table.column align="end">Coût</flux:table.column>
                <flux:table.column align="end">Prix</flux:table.column>
                <flux:table.column align="end">Stock total</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($products as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell class="text-zinc-500">{{ $product->sku }}<div class="text-xs">{{ $product->barcode }}</div></flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">{{ $product->name }}</div>
                            <div class="flex gap-1">
                                @if ($product->is_sellable_pos)<flux:badge size="sm" color="emerald">POS</flux:badge>@endif
                                @if (! $product->is_active)<flux:badge size="sm" color="red">Inactif</flux:badge>@endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->type->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $product->category?->name }}</flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$product->cost_price" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$product->sale_price" /></flux:table.cell>
                        <flux:table.cell align="end" @class(['font-medium', 'text-red-600' => $product->is_stockable && (float) $product->stock_total <= (float) $product->min_stock])>
                            {{ $product->is_stockable ? rtrim(rtrim(number_format((float) $product->stock_total, 3, ',', ' '), '0'), ',').' '.$product->unit?->symbol : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>@can('products.update')<flux:button size="xs" variant="ghost" icon="pencil" wire:click="edit({{ $product->id }})" />@endcan</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="product" class="w-full max-w-3xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Modifier l’article' : 'Nouvel article' }}</flux:heading>
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="form.sku" label="SKU" />
                <flux:input wire:model="form.barcode" label="Code-barres" />
                <flux:input wire:model="form.rfid_tag" label="Tag RFID" />
                <flux:input wire:model="form.name" label="Nom" class="md:col-span-2" />
                <flux:select wire:model="form.type" label="Type">@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.product_category_id" label="Catégorie"><flux:select.option value="">—</flux:select.option>@foreach ($categories as $c)<flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.unit_id" label="Unité"><flux:select.option value="">—</flux:select.option>@foreach ($units as $u)<flux:select.option :value="$u->id">{{ $u->name }} ({{ $u->symbol }})</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.supplier_id" label="Fournisseur"><flux:select.option value="">—</flux:select.option>@foreach ($suppliers as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="form.cost_price" type="number" step="0.01" label="Coût" />
                <flux:input wire:model="form.sale_price" type="number" step="0.01" label="Prix de vente" />
                <flux:input wire:model="form.tax_rate" type="number" step="0.01" label="TVA %" />
                <flux:input wire:model="form.min_stock" type="number" step="any" label="Stock minimum" />
                <flux:input wire:model="form.max_stock" type="number" step="any" label="Stock maximum" />
                <flux:input type="file" wire:model="image" label="Image" accept="image/*" />
            </div>
            <flux:textarea wire:model="form.description" label="Description" rows="2" />
            <div class="flex flex-wrap gap-6">
                <flux:switch wire:model="form.is_stockable" label="Géré en stock" />
                <flux:switch wire:model="form.is_sellable_pos" label="Vendu en boutique (POS)" />
                <flux:switch wire:model="form.is_favorite" label="Favori POS" />
                <flux:switch wire:model="form.is_active" label="Actif" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
