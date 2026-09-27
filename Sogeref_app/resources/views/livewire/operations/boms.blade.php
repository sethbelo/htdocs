<div>
    <x-ui.page-header title="Nomenclatures" eyebrow="Atelier" :back="route('manufacturing')" subtitle="Composition de chaque produit fabriqué : la liste de matériel des ordres de fabrication en découle automatiquement.">
        <x-slot:actions>
            @if (auth()->user()->canManage('manufacturing'))<x-ui.button variant="accent" icon="plus" wire:click="create">Nouvelle nomenclature</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @forelse ($boms as $bom)
            @php $cost = $bom->materialCost(); @endphp
            <x-ui.card :title="$bom->product->name" :subtitle="$bom->product->sku.' · pour '.qty($bom->yield_quantity).' '.$bom->product->unit" icon="rectangle-group" :padded="false">
                <x-slot:actions>
                    @if (auth()->user()->canManage('manufacturing'))
                        <x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="edit({{ $bom->id }})" aria-label="Modifier" />
                        <x-ui.button variant="ghost" size="sm" square icon="trash" wire:click="delete({{ $bom->id }})" wire:confirm="Supprimer cette nomenclature ?" aria-label="Supprimer" />
                    @endif
                </x-slot:actions>
                <ul class="divide-y divide-line">
                    @foreach ($bom->lines as $line)
                        <li class="flex justify-between gap-3 px-5 py-2 text-[13px]"><span class="truncate">{{ $line->product->name }}</span><span class="shrink-0 font-semibold tabular-nums">{{ qty($line->quantity, 3) }} {{ $line->product->unit }}</span></li>
                    @endforeach
                </ul>
                <div class="grid grid-cols-3 gap-2 border-t border-line bg-surface-2 px-5 py-3 text-[12px]">
                    <div><p class="text-fg-subtle">Coût matières</p><p class="font-semibold">{{ money($cost) }}</p></div>
                    <div><p class="text-fg-subtle">Main-d’œuvre</p><p class="font-semibold">{{ qty($bom->labour_hours) }} h</p></div>
                    <div><p class="text-fg-subtle">Marge / prix</p><p @class(['font-semibold', 'text-emerald-600' => $bom->product->sale_price > $cost])>{{ (float) $bom->product->sale_price ? round((1 - $cost / (float) $bom->product->sale_price) * 100).' %' : '—' }}</p></div>
                </div>
            </x-ui.card>
        @empty
            <div class="card md:col-span-2"><x-ui.empty icon="rectangle-group" title="Aucune nomenclature" text="Décrivez la composition d’un produit pour automatiser vos listes de matériel." /></div>
        @endforelse
    </div>

    <x-ui.drawer wire:model="showForm" :title="$editing ? 'Modifier la nomenclature' : 'Nouvelle nomenclature'" icon="rectangle-group" size="lg">
        <div class="space-y-4">
            <x-ui.select label="Produit fabriqué" wire:model="productId" placeholder="Choisir…" :options="$finished->mapWithKeys(fn ($p) => [$p->id => $p->sku.' · '.$p->name])" required />
            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Quantité produite" type="number" step="0.01" wire:model="yield" hint="Les quantités ci-dessous valent pour cette quantité." />
                <x-ui.input label="Main-d’œuvre (heures)" type="number" step="0.5" wire:model="labourHours" />
            </div>
            <div>
                <p class="label">Composants</p>
                <div class="space-y-2">
                    @foreach ($lines as $i => $line)
                        <div class="flex gap-2" wire:key="bl-{{ $i }}">
                            <select wire:model.live="lines.{{ $i }}.product_id" @class(['input flex-1', 'input-error' => $errors->has("lines.$i.product_id")])>
                                <option value="">Matière…</option>
                                @foreach ($components as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->unit }})</option>@endforeach
                            </select>
                            <input type="number" step="0.001" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" class="input w-28 text-right">
                            <button type="button" wire:click="removeLine({{ $i }})" class="btn btn-ghost btn-icon text-fg-subtle"><x-ui.icon name="x-mark" /></button>
                        </div>
                    @endforeach
                </div>
                <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addLine" class="mt-2">Ajouter un composant</x-ui.button>
            </div>
            <p class="rounded-xl bg-surface-2 px-4 py-3 text-[13px]">Coût matières estimé : <strong>{{ money($formCost) }}</strong></p>
            <x-ui.textarea label="Notes de fabrication" wire:model="notes" rows="2" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
