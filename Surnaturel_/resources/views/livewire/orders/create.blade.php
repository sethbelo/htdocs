<div class="space-y-6">
    <x-page-header title="Nouvelle commande" subtitle="Le numéro CMD-AAAA-NNNNNN est attribué à l’enregistrement ; le ticket de caisse est imprimé immédiatement." />

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="panel p-4 space-y-4">
                <flux:heading>Client</flux:heading>
                @if ($this->client)
                    <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                        <div>
                            <div class="font-medium">{{ $this->client->displayName() }}</div>
                            <div class="text-sm text-zinc-500">{{ $this->client->number }} · {{ $this->client->phone }} · {{ $this->client->type->label() }}</div>
                        </div>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="$set('client_id', null)">Changer</flux:button>
                    </div>
                @else
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <flux:input wire:model.live.debounce.250ms="clientSearch" icon="magnifying-glass" placeholder="Rechercher un client (nom, n°, téléphone)…" autofocus />
                            @if ($this->clientResults->isNotEmpty())
                                <div class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                    @foreach ($this->clientResults as $result)
                                        <button type="button" wire:click="selectClient({{ $result->id }})" wire:key="cr-{{ $result->id }}" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                            <span class="font-medium">{{ $result->displayName() }}</span>
                                            <span class="text-zinc-500">· {{ $result->number }} · {{ $result->phone }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @can('clients.create')
                            <flux:modal.trigger name="new-client">
                                <flux:button icon="user-plus">Nouveau</flux:button>
                            </flux:modal.trigger>
                        @endcan
                    </div>
                    <flux:error name="client_id" />
                @endif
            </div>

            <div class="panel p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading>Produits & services</flux:heading>
                    <flux:button size="sm" icon="plus" wire:click="addItem">Ajouter une ligne</flux:button>
                </div>

                @foreach ($items as $index => $item)
                    <div class="rounded-lg border border-zinc-200 p-3 space-y-3 dark:border-zinc-700" wire:key="item-{{ $index }}">
                        <div class="grid gap-3 md:grid-cols-12">
                            <flux:select wire:model.live="items.{{ $index }}.product_id" label="Article" class="md:col-span-4">
                                <flux:select.option value="">— Personnalisé —</flux:select.option>
                                @foreach ($products as $product)
                                    <flux:select.option :value="$product->id">{{ $product->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:input wire:model="items.{{ $index }}.description" label="Désignation" class="md:col-span-8" />
                            <flux:input wire:model.live.debounce.400ms="items.{{ $index }}.quantity" type="number" step="any" label="Quantité / ex." class="md:col-span-2" />
                            <flux:input wire:model.live.debounce.400ms="items.{{ $index }}.unit_price" type="number" step="0.01" label="Prix unitaire" class="md:col-span-3" />
                            <flux:input wire:model.live.debounce.400ms="items.{{ $index }}.discount" type="number" step="0.01" label="Remise" class="md:col-span-2" />
                            <flux:input wire:model.live.debounce.400ms="items.{{ $index }}.tax_rate" type="number" step="0.01" label="TVA %" class="md:col-span-2" />
                            <div class="md:col-span-3 flex items-end justify-end gap-2">
                                <div class="text-right">
                                    <div class="text-xs text-zinc-500">Total ligne</div>
                                    <div class="font-semibold"><x-money :amount="app(\App\Services\Support\TotalsCalculator::class)->line(['quantity' => (float) $item['quantity'], 'unit_price' => (float) $item['unit_price'], 'discount' => (float) $item['discount'], 'tax_rate' => (float) $item['tax_rate']])['total']" /></div>
                                </div>
                                @if (count($items) > 1)
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $index }})" />
                                @endif
                            </div>
                        </div>

                        <details class="group" open>
                            <summary class="cursor-pointer text-sm font-medium text-zinc-600 dark:text-zinc-300">Spécifications techniques</summary>
                            <div class="mt-3 grid gap-3 md:grid-cols-6">
                                <flux:input wire:model="items.{{ $index }}.format" label="Format" placeholder="A4, A5, 85×55…" />
                                <flux:input wire:model="items.{{ $index }}.width" type="number" step="any" label="Largeur" />
                                <flux:input wire:model="items.{{ $index }}.height" type="number" step="any" label="Hauteur" />
                                <flux:select wire:model="items.{{ $index }}.dimension_unit" label="Unité">
                                    <flux:select.option value="mm">mm</flux:select.option>
                                    <flux:select.option value="cm">cm</flux:select.option>
                                    <flux:select.option value="m">m</flux:select.option>
                                </flux:select>
                                <flux:input wire:model="items.{{ $index }}.paper" label="Papier / support" class="md:col-span-2" />
                                <flux:input wire:model="items.{{ $index }}.grammage" type="number" label="Grammage (g/m²)" />
                                <flux:input wire:model="items.{{ $index }}.colors" label="Couleurs" placeholder="Quadri, N&B, Pantone…" />
                                <flux:select wire:model="items.{{ $index }}.print_sides" label="Impression">
                                    <flux:select.option value="recto">Recto</flux:select.option>
                                    <flux:select.option value="recto_verso">Recto/Verso</flux:select.option>
                                </flux:select>
                                <flux:input wire:model="items.{{ $index }}.pages" type="number" label="Pages" />
                                <flux:input wire:model="items.{{ $index }}.finishing" label="Finition" />
                                <flux:input wire:model="items.{{ $index }}.binding" label="Reliure" />
                                <flux:input wire:model="items.{{ $index }}.cutting" label="Découpe" />
                                <flux:input wire:model="items.{{ $index }}.film_coating" label="Pelliculage" />
                                <div class="flex items-end pb-2"><flux:checkbox wire:model="items.{{ $index }}.lamination" label="Plastification" /></div>
                            </div>
                        </details>
                    </div>
                @endforeach
                <flux:error name="items" />
            </div>

            <div class="panel p-4">
                <flux:textarea wire:model="technical_notes" label="Commentaire technique" rows="3" placeholder="Consignes pour le graphiste et l’atelier, fichiers client, contraintes…" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="panel p-4 space-y-4">
                <flux:heading>Paramètres</flux:heading>
                <flux:select wire:model="site_id" label="Siège">
                    @foreach ($sites as $site)
                        <flux:select.option :value="$site->id">{{ $site->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="due_at" type="datetime-local" label="Délai souhaité" />
                <flux:radio.group wire:model="priority" label="Priorité" variant="segmented" size="sm">
                    @foreach ($priorities as $value => $label)
                        <flux:radio :value="$value" :label="$label" />
                    @endforeach
                </flux:radio.group>
                <flux:switch wire:model="requires_design" label="Nécessite une conception graphique" description="Sinon, la commande part directement en file d’impression après paiement." />
            </div>

            <div class="panel p-4 space-y-3 xl:sticky xl:top-6">
                <flux:heading>Montants</flux:heading>
                <flux:input wire:model.live.debounce.400ms="global_discount" type="number" step="0.01" label="Remise globale" />
                <flux:input wire:model.live.debounce.400ms="deposit_rate" type="number" step="1" label="Acompte exigé (%)" description="100 % = paiement intégral avant production." />
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Sous-total</dt><dd><x-money :amount="$this->totals['subtotal']" /></dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Remises</dt><dd>− <x-money :amount="$this->totals['discount_total']" /></dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">TVA</dt><dd><x-money :amount="$this->totals['tax_total']" /></dd></div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-700"><dt>Total TTC</dt><dd><x-money :amount="$this->totals['total']" /></dd></div>
                    <div class="flex justify-between text-indigo-600 dark:text-indigo-400"><dt>À encaisser (acompte)</dt><dd class="font-semibold"><x-money :amount="$this->totals['deposit']" /></dd></div>
                </dl>
                <flux:error name="global_discount" />
                <div class="flex flex-col gap-2 pt-2">
                    <flux:button type="submit" variant="primary" icon="printer">Enregistrer & imprimer le ticket</flux:button>
                    <flux:button type="button" wire:click="save(true)" variant="ghost">Enregistrer en brouillon</flux:button>
                </div>
            </div>
        </div>
    </form>

    <flux:modal name="new-client" class="w-full max-w-md">
        <form wire:submit="createClient" class="space-y-4">
            <flux:heading size="lg">Nouveau client</flux:heading>
            <flux:select wire:model="newClient.type" label="Type">
                @foreach ($clientTypes as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="newClient.name" label="Nom / contact" required />
            <flux:input wire:model="newClient.company_name" label="Entreprise / organisation" />
            <flux:input wire:model="newClient.phone" label="Téléphone / WhatsApp" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Créer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
