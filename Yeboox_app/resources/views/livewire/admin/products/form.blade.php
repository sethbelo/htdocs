<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $editing ? 'Modifier le produit' : 'Nouveau produit' }}</flux:heading>
            <flux:subheading class="mt-1">Références, tarification, unité et comportement du produit dans un seul écran.</flux:subheading>
        </div>
        <flux:button href="{{ route('admin.products.index') }}" wire:navigate icon="arrow-left">Retour au catalogue</flux:button>
    </div>

    @if ($errors->any())
        <x-dismissible-alert variant="danger" icon="exclamation-triangle">
            Certaines informations sont incomplètes. Vérifiez les champs signalés avant d’enregistrer.
        </x-dismissible-alert>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
            <div class="space-y-6">
                <flux:card class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="yeboox-icon-tile" style="--tone:#2563eb"><flux:icon.information-circle class="size-5" /></div>
                        <div><flux:heading size="lg">Identité du produit</flux:heading><flux:text>Les informations utilisées dans le catalogue, la caisse et les documents.</flux:text></div>
                    </div>

                    <flux:input wire:model="name" label="Nom du produit" placeholder="Ex. Poulet braisé" required />

                    <div class="grid gap-5 lg:grid-cols-2">
                        <flux:field>
                            <flux:label>SKU</flux:label>
                            <div class="flex items-stretch gap-2">
                                <flux:input wire:model="sku" class="min-w-0 flex-1" placeholder="Généré automatiquement si vide" />
                                <flux:button type="button" wire:click="generateSku" icon="sparkles" :disabled="$sku !== ''">Générer</flux:button>
                            </div>
                            <flux:description>Votre référence interne unique.</flux:description>
                            <flux:error name="sku" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Code-barres</flux:label>
                            <div class="flex items-stretch gap-2">
                                <flux:input wire:model="barcode" inputmode="numeric" class="min-w-0 flex-1" placeholder="EAN-13" />
                                <flux:button type="button" wire:click="generateBarcode" icon="qr-code" :disabled="$barcode !== ''">Générer</flux:button>
                            </div>
                            <flux:description>Un EAN-13 valide est créé lorsque le champ est vide.</flux:description>
                            <flux:error name="barcode" />
                        </flux:field>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <flux:select wire:model="category_id" label="Catégorie" required>
                            <flux:select.option value="">Sélectionner une catégorie</flux:select.option>
                            @foreach ($categories as $category)<flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>@endforeach
                        </flux:select>
                        <flux:select wire:model="unit_id" label="Unité de mesure">
                            <flux:select.option value="">Sans unité</flux:select.option>
                            @foreach ($units as $unit)<flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>@endforeach
                        </flux:select>
                    </div>
                    <flux:textarea wire:model="description" label="Description" rows="3" placeholder="Composition, usage ou informations utiles…" />
                </flux:card>

                <flux:card class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="yeboox-icon-tile" style="--tone:#8b5cf6"><flux:icon.squares-2x2 class="size-5" /></div>
                        <div><flux:heading size="lg">Type de produit</flux:heading><flux:text>Le formulaire s’adapte automatiquement au fonctionnement choisi.</flux:text></div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" role="radiogroup" aria-label="Type de produit">
                        @foreach ([
                            'simple' => ['cube', 'Simple', 'Un seul prix et stock'],
                            'variable' => ['squares-2x2', 'Variable', 'Tailles, couleurs…'],
                            'composite' => ['puzzle-piece', 'Composé', 'Recette ou ensemble'],
                            'service' => ['wrench-screwdriver', 'Service', 'Sans gestion de stock'],
                        ] as $value => [$icon, $label, $help])
                            <button type="button" wire:click="$set('product_type', '{{ $value }}')" role="radio" aria-checked="{{ $product_type === $value ? 'true' : 'false' }}"
                                class="rounded-2xl border p-4 text-left transition {{ $product_type === $value ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500/20 dark:bg-primary-950/30' : 'border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}">
                                <span class="mb-3 flex size-10 items-center justify-center rounded-xl {{ $product_type === $value ? 'bg-primary-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" />
                                </span>
                                <span class="block font-semibold text-zinc-900 dark:text-white">{{ $label }}</span>
                                <span class="mt-1 block text-xs text-zinc-500">{{ $help }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if ($product_type === 'service')
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                            <div class="flex gap-3"><flux:icon.information-circle class="mt-0.5 size-5 shrink-0" /><p><strong>Produit non stockable.</strong> Le suivi et l’alerte de stock sont désactivés automatiquement pour ce service.</p></div>
                        </div>
                    @endif
                </flux:card>

                @if ($product_type === 'variable')
                    <flux:card class="space-y-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div><flux:heading size="lg">Variantes</flux:heading><flux:text>Ajoutez les déclinaisons vendues séparément.</flux:text></div>
                            <flux:button type="button" wire:click="addVariant" icon="plus" variant="primary">Ajouter une variante</flux:button>
                        </div>
                        <div class="space-y-4">
                            @forelse ($variants as $index => $variant)
                                <div wire:key="variant-{{ $variant['id'] ?? 'new-'.$index }}" class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                                    <div class="mb-4 flex items-center justify-between"><flux:heading>Variante {{ $index + 1 }}</flux:heading><flux:button type="button" wire:click="removeVariant({{ $index }})" icon="trash" variant="danger" size="sm">Retirer</flux:button></div>
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        <flux:input wire:model="variants.{{ $index }}.name" label="Nom" placeholder="Ex. Grande / Rouge" required />
                                        <flux:field><flux:label>SKU</flux:label><div class="flex gap-2"><flux:input wire:model="variants.{{ $index }}.sku" class="min-w-0 flex-1" /><flux:button type="button" wire:click="generateVariantSku({{ $index }})" icon="sparkles" square aria-label="Générer le SKU" /></div><flux:error name="variants.{{ $index }}.sku" /></flux:field>
                                        <flux:field><flux:label>Code-barres</flux:label><div class="flex gap-2"><flux:input wire:model="variants.{{ $index }}.barcode" class="min-w-0 flex-1" /><flux:button type="button" wire:click="generateVariantBarcode({{ $index }})" icon="qr-code" square aria-label="Générer le code-barres" /></div><flux:error name="variants.{{ $index }}.barcode" /></flux:field>
                                        <flux:input wire:model="variants.{{ $index }}.cost_price" type="number" min="0" step="0.01" label="Prix d’achat" suffix="FC" required />
                                        <flux:input wire:model="variants.{{ $index }}.selling_price" type="number" min="0" step="0.01" label="Prix de vente" suffix="FC" required />
                                        @if ($track_stock)<flux:input wire:model="variants.{{ $index }}.stock_quantity" type="number" min="0" step="0.01" label="Stock initial" :suffix="$units->firstWhere('id', $unit_id)?->abbreviation" />@endif
                                    </div>
                                </div>
                            @empty
                                <div class="yeboox-empty py-10"><div><flux:icon.squares-plus class="mx-auto size-10 text-zinc-300" /><flux:heading class="mt-3">Aucune variante</flux:heading><flux:text>Ajoutez au moins une déclinaison.</flux:text></div></div>
                            @endforelse
                        </div>
                    </flux:card>
                @endif

                @if ($product_type === 'composite')
                    <flux:card class="space-y-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div><flux:heading size="lg">Composition</flux:heading><flux:text>Définissez les produits consommés pour fabriquer ou vendre cet ensemble.</flux:text></div>
                            <flux:button type="button" wire:click="addComponent" icon="plus" variant="primary">Ajouter un composant</flux:button>
                        </div>
                        <div class="space-y-3">
                            @forelse ($components as $index => $component)
                                <div wire:key="component-{{ $index }}" class="grid gap-4 rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 md:grid-cols-[minmax(0,1fr)_10rem_12rem_auto] md:items-end dark:border-zinc-700 dark:bg-zinc-900/50">
                                    <flux:select wire:model="components.{{ $index }}.product_id" label="Produit composant" required><flux:select.option value="">Sélectionner</flux:select.option>@foreach ($componentProducts as $item)<flux:select.option value="{{ $item->id }}">{{ $item->name }} · {{ $item->sku ?: 'sans SKU' }}</flux:select.option>@endforeach</flux:select>
                                    <flux:input wire:model="components.{{ $index }}.quantity" type="number" min="0.0001" step="0.0001" label="Quantité" required />
                                    <flux:select wire:model="components.{{ $index }}.unit_id" label="Unité"><flux:select.option value="">Unité du produit</flux:select.option>@foreach ($units as $unit)<flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>@endforeach</flux:select>
                                    <flux:button type="button" wire:click="removeComponent({{ $index }})" icon="trash" variant="danger" square aria-label="Retirer ce composant" />
                                </div>
                            @empty
                                <div class="yeboox-empty py-10"><div><flux:icon.puzzle-piece class="mx-auto size-10 text-zinc-300" /><flux:heading class="mt-3">Composition vide</flux:heading><flux:text>Ajoutez au moins un produit composant.</flux:text></div></div>
                            @endforelse
                        </div>
                    </flux:card>
                @endif

                <flux:card class="space-y-5">
                    <div class="flex items-start gap-4"><div class="yeboox-icon-tile" style="--tone:#10b981"><flux:icon.banknotes class="size-5" /></div><div><flux:heading size="lg">Tarification</flux:heading><flux:text>Prix de base, promotion et fiscalité.</flux:text></div></div>
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <flux:input wire:model.live="cost_price" type="number" min="0" step="0.01" label="Prix d’achat" suffix="FC" required />
                        <flux:input wire:model.live="selling_price" type="number" min="0" step="0.01" label="Prix de vente" suffix="FC" required />
                        <flux:input wire:model="promo_price" type="number" min="0" step="0.01" label="Prix promotionnel" suffix="FC" />
                        <flux:select wire:model="tax_id" label="Taxe"><flux:select.option value="">Aucune taxe</flux:select.option>@foreach ($taxes as $tax)<flux:select.option value="{{ $tax->id }}">{{ $tax->name }} ({{ number_format($tax->rate, 2, ',', ' ') }} %)</flux:select.option>@endforeach</flux:select>
                    </div>
                    @if ((float) $selling_price > 0)
                        @php($margin = (((float) $selling_price - (float) $cost_price) / (float) $selling_price) * 100)
                        <div class="grid gap-3 sm:grid-cols-2"><div class="yeboox-card-tone p-4" style="--tone:#10b981"><flux:text size="sm">Bénéfice unitaire</flux:text><p class="mt-1 text-xl font-semibold">{{ number_format((float) $selling_price - (float) $cost_price, 0, ',', '.') }} FC</p></div><div class="yeboox-card-tone p-4" style="--tone:{{ $margin >= 30 ? '#10b981' : '#f59e0b' }}"><flux:text size="sm">Marge brute</flux:text><p class="mt-1 text-xl font-semibold">{{ number_format($margin, 1, ',', '.') }} %</p></div></div>
                    @endif
                </flux:card>
            </div>

            <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
                <flux:card class="space-y-5">
                    <div><flux:heading>Image ou icône</flux:heading><flux:text size="sm">L’image est prioritaire dans le catalogue.</flux:text></div>
                    <div class="mx-auto flex aspect-square w-full max-w-72 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="Aperçu du produit" class="size-full object-cover" />
                        @elseif ($existing_image_url)
                            <img src="{{ $existing_image_url }}" alt="Image actuelle du produit" class="size-full object-cover" />
                        @else
                            <x-dynamic-component :component="'heroicon-o-'.$product_icon" class="size-16 text-zinc-300 dark:text-zinc-600" />
                        @endif
                    </div>
                    <flux:input wire:model="image" type="file" accept="image/png,image/jpeg,image/webp" label="Photo du produit" description="PNG, JPG ou WebP · 3 Mo maximum" />
                    <flux:select wire:model.live="product_icon" label="Icône de remplacement">@foreach ($availableIcons as $icon => $label)<flux:select.option value="{{ $icon }}">{{ $label }}</flux:select.option>@endforeach</flux:select>
                </flux:card>

                <flux:card class="space-y-5">
                    <div><flux:heading>Stock et visibilité</flux:heading><flux:text size="sm">Contrôlez le comportement opérationnel.</flux:text></div>
                    <flux:switch wire:model.live="track_stock" label="Suivre le stock" description="Mouvements et alertes de quantité." :disabled="$product_type === 'service'" />
                    @if ($track_stock && $product_type !== 'service')
                        <flux:input wire:model="alert_quantity" type="number" min="0" step="0.01" label="Seuil d’alerte" :suffix="$units->firstWhere('id', $unit_id)?->abbreviation" />
                    @endif
                    <flux:switch wire:model="is_pos_visible" label="Visible au point de vente" />
                    <flux:switch wire:model="is_active" label="Produit actif" />
                    <flux:input wire:model="sort_order" type="number" min="0" label="Ordre d’affichage" />
                </flux:card>
            </aside>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:justify-end dark:border-zinc-700">
            <flux:button href="{{ route('admin.products.index') }}" wire:navigate>Annuler</flux:button>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $editing ? 'Enregistrer les modifications' : 'Créer le produit' }}</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </flux:button>
        </div>
    </form>
</div>
