<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$document ? $labels[0].' '.$document->number : $labels[1]" eyebrow="Éditeur de document" :back="$backUrl">
        <x-slot:actions>
            <x-ui.button variant="ghost" :href="$backUrl">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            {{-- ═══ 2. EN-TÊTE DU DOCUMENT ═══════════════════════════════════ --}}
            <x-ui.card title="Informations" icon="identification">
                @if ($type === 'quote')
                    <div class="mb-5">
                        <p class="label">Intitulé du document</p>
                        <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Intitulé du document">
                            @foreach (['devis' => ['Devis', 'Proposition chiffrée à faire accepter par le client.', 'document-text'], 'proforma' => ['Facture pro forma', 'Facture préalable pour commande, import, banque ou paiement anticipé.', 'document-duplicate']] as $value => [$label, $help, $icon])
                                <label @class(['flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition', 'border-brand-600 bg-brand-50/60 ring-2 ring-sun-400 dark:bg-white/6' => $heading === $value, 'border-line hover:border-line-strong' => $heading !== $value])>
                                    <input type="radio" wire:model.live="heading" value="{{ $value }}" class="mt-1">
                                    <span><span class="flex items-center gap-1.5 font-semibold"><x-ui.icon :name="$icon" class="size-4" />{{ $label }}</span><span class="mt-0.5 block text-[12px] text-fg-subtle">{{ $help }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-ui.select class="md:col-span-2" :label="$type === 'purchase_order' ? 'Fournisseur' : 'Client'" wire:model.live="contact_id" placeholder="Sélectionner…" required>
                        @foreach ($contacts as $c)<option value="{{ $c->id }}">{{ $c->display_name }} — {{ $c->reference }}</option>@endforeach
                    </x-ui.select>
                    <x-ui.input label="Date" type="date" wire:model="issue_date" required />
                    <x-ui.input :label="$type === 'quote' ? 'Valable jusqu’au' : 'Échéance'" type="date" wire:model="due_date" />
                    <x-ui.select label="Devise" wire:model.live="currency_code" :options="['USD' => 'USD — Dollar américain', 'CDF' => 'CDF — Franc congolais', 'EUR' => 'EUR — Euro']" />
                    <x-ui.input label="Taux (1 unité = x USD)" type="number" step="0.0001" wire:model="exchange_rate" :hint="$currency_code === 'USD' ? 'Devise de base' : 'Pour la conversion comptable'" />
                </div>
                @if ($contact)
                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-1 rounded-xl bg-surface-2 px-4 py-3 text-[12.5px] text-fg-muted">
                        <span class="font-semibold text-fg">{{ $contact->display_name }}</span>
                        <span>{{ strtoupper($contact->segment) }}</span>
                        @if ($contact->phone)<span>{{ $contact->phone }}</span>@endif
                        <span>Paiement : {{ $contact->payment_terms ? $contact->payment_terms.' jours' : 'comptant' }}</span>
                        @if ((float) $contact->discount_rate)<x-ui.badge tone="success">Remise négociée {{ qty($contact->discount_rate) }} %</x-ui.badge>@endif
                    </div>
                @endif
            </x-ui.card>

            {{-- ═══ 3. LIGNES ════════════════════════════════════════════════ --}}
            <x-ui.card title="Lignes" subtitle="Articles, matières et prestations" icon="list-bullet" :padded="false">
                <x-slot:actions><x-ui.button variant="soft" size="sm" icon="plus" wire:click="addLine">Ajouter une ligne</x-ui.button></x-slot:actions>
                <div class="table-wrap">
                    <table class="table min-w-[980px]">
                        <thead><tr><th class="w-8"></th><th class="w-[30%]">Article / désignation</th><th class="w-24">Qté</th><th class="w-28">Unité</th><th class="w-32">P.U. HT</th><th class="w-24">Rem. %</th><th class="w-24">TVA %</th><th class="num w-32">Total HT</th><th class="w-10"></th></tr></thead>
                        <tbody>
                            @foreach ($items as $i => $item)
                                @php $lineTotal = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0) * (1 - (float) ($item['discount_rate'] ?? 0) / 100); @endphp
                                <tr wire:key="line-{{ $i }}" class="align-top">
                                    <td class="pt-4 text-center">
                                        <div class="flex flex-col items-center gap-0.5 text-fg-subtle">
                                            <button type="button" wire:click="moveLine({{ $i }}, -1)" class="hover:text-fg" aria-label="Monter"><x-ui.icon name="chevron-up" class="size-3.5" /></button>
                                            <span class="text-[11px] font-semibold">{{ $i + 1 }}</span>
                                            <button type="button" wire:click="moveLine({{ $i }}, 1)" class="hover:text-fg" aria-label="Descendre"><x-ui.icon name="chevron-down" class="size-3.5" /></button>
                                        </div>
                                    </td>
                                    <td class="space-y-2">
                                        <select wire:model.live="items.{{ $i }}.product_id" class="input">
                                            <option value="">— Ligne libre —</option>
                                            @foreach ($products->groupBy('type') as $ptype => $group)
                                                <optgroup label="{{ config('erp.product_types.'.$ptype) }}">
                                                    @foreach ($group as $p)<option value="{{ $p->id }}">{{ $p->sku }} · {{ $p->name }}</option>@endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <textarea wire:model.blur="items.{{ $i }}.description" rows="2" @class(['input text-[13px]', 'input-error' => $errors->has("items.$i.description")]) placeholder="Désignation détaillée (dimensions, finition…)"></textarea>
                                    </td>
                                    <td><input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="items.{{ $i }}.quantity" @class(['input text-right', 'input-error' => $errors->has("items.$i.quantity")])></td>
                                    <td>
                                        <input list="units" wire:model.blur="items.{{ $i }}.unit" class="input">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="items.{{ $i }}.unit_price" class="input text-right"></td>
                                    <td><input type="number" step="0.5" min="0" max="100" wire:model.live.debounce.400ms="items.{{ $i }}.discount_rate" class="input text-right"></td>
                                    <td><input type="number" step="0.5" min="0" max="100" wire:model.live.debounce.400ms="items.{{ $i }}.tax_rate" class="input text-right"></td>
                                    <td class="num pt-5 font-semibold">{{ money($lineTotal, $currency_code) }}</td>
                                    <td class="pt-3.5"><button type="button" wire:click="removeLine({{ $i }})" class="btn btn-ghost btn-sm btn-icon text-fg-subtle hover:text-flame-600" aria-label="Supprimer la ligne"><x-ui.icon name="trash" class="size-4" /></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <datalist id="units">@foreach (config('erp.units') as $unit)<option value="{{ $unit }}">@endforeach</datalist>
                </div>
                <div class="border-t border-line p-3"><x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addLine">Ajouter une ligne</x-ui.button></div>
            </x-ui.card>

            {{-- ═══ 4. NOTES & CONDITIONS ════════════════════════════════════ --}}
            <x-ui.card title="Notes et conditions" icon="chat-bubble-bottom-center-text">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.textarea label="N.B. / observations (visible sur le document)" wire:model="notes" rows="4" placeholder="Délais, modalités de livraison, précisions techniques…" />
                    <x-ui.textarea label="Conditions" wire:model="terms" rows="4" />
                </div>
            </x-ui.card>
        </div>

        {{-- ═══ 5. COLONNE LATÉRALE ══════════════════════════════════════════ --}}
        <aside class="space-y-6 xl:sticky xl:top-20 xl:self-start">
            <x-ui.card title="Totaux" icon="calculator">
                <dl class="space-y-2 text-[13.5px]">
                    <div class="flex justify-between"><dt class="text-fg-muted">Total HT brut</dt><dd class="tabular-nums">{{ money($this->totals['subtotal'], $currency_code) }}</dd></div>
                    @if ($this->totals['discount'] > 0)<div class="flex justify-between"><dt class="text-fg-muted">Remises</dt><dd class="tabular-nums text-emerald-600">− {{ money($this->totals['discount'], $currency_code) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-fg-muted">TVA</dt><dd class="tabular-nums">{{ money($this->totals['tax'], $currency_code) }}</dd></div>
                    <div class="flex items-baseline justify-between border-t border-line pt-3"><dt class="font-semibold">Total TTC</dt><dd class="font-display text-[24px] font-semibold tabular-nums text-brand-600 dark:text-lagoon-300">{{ money($this->totals['total'], $currency_code) }}</dd></div>
                </dl>
                <p class="mt-2 text-[12px] text-fg-subtle">{{ \App\Support\AmountInWords::make($this->totals['total'], $currency_code) }}</p>
            </x-ui.card>

            <x-ui.card title="Modèle d’impression" icon="swatch">
                <div class="grid grid-cols-2 gap-2">
                    @foreach (config('erp.document_templates') as $key => $tpl)
                        <button type="button" wire:click="$set('template', '{{ $key }}')" @class(['rounded-xl border p-2.5 text-left transition', 'border-brand-600 ring-2 ring-sun-400' => $template === $key, 'border-line hover:border-line-strong' => $template !== $key])>
                            <span class="mb-2 block h-14 overflow-hidden rounded-md border border-line bg-white p-1.5">
                                @switch($key)
                                    @case('moderne')<span class="block h-2 w-8 rounded-sm bg-sun-400"></span><span class="mt-1 block h-1 rounded bg-brand-600"></span><span class="mt-1.5 block h-3 rounded-sm bg-brand-600"></span><span class="mt-1 block h-1 w-2/3 rounded bg-ink-200"></span>@break
                                    @case('classique')<span class="flex gap-1"><span class="block h-4 flex-1 rounded-sm bg-ink-200"></span><span class="block h-4 flex-1 rounded-sm border border-ink-300"></span></span><span class="mt-1.5 block h-5 rounded-sm border border-ink-400"></span>@break
                                    @case('epure')<span class="block h-3 w-10 rounded-sm bg-ink-900"></span><span class="mt-2 block h-px bg-ink-900"></span><span class="mt-1.5 block h-1 w-3/4 rounded bg-ink-200"></span><span class="mt-1 block h-1 w-1/2 rounded bg-ink-200"></span>@break
                                    @case('chantier')<span class="block h-4 rounded-sm bg-brand-50 ring-1 ring-brand-200"></span><span class="mt-1 block h-2 rounded-sm bg-brand-600"></span><span class="mt-1 flex gap-1"><span class="block h-2 flex-1 rounded-sm border border-dashed border-ink-400"></span><span class="block h-2 flex-1 rounded-sm border border-dashed border-ink-400"></span><span class="block h-2 flex-1 rounded-sm border border-dashed border-ink-400"></span></span>@break
                                    @case('prestige')<span class="block h-3 rounded-sm bg-sun-400"></span><span class="mt-1 block h-4 rounded-sm border-2 border-brand-600"></span><span class="mt-1 block h-1.5 rounded-sm bg-sun-400"></span><span class="mt-1 ml-auto block h-1.5 w-1/2 rounded-sm bg-brand-600"></span>@break
                                @endswitch
                            </span>
                            <span class="block text-[13px] font-semibold">{{ $tpl['label'] }}</span>
                            <span class="block text-[11px] leading-snug text-fg-subtle">{{ $tpl['description'] }}</span>
                            <a href="{{ route('pdf.preview', ['kind' => $type === 'quote' && $heading === 'proforma' ? 'proforma' : $type, 'template' => $key]) }}" target="_blank" class="mt-1.5 inline-flex items-center gap-1 text-[11.5px] font-semibold text-brand-600 hover:underline dark:text-lagoon-300" x-on:click.stop><x-ui.icon name="eye" class="size-3.5" />Aperçu</a>
                        </button>
                    @endforeach
                </div>
            </x-ui.card>

            @if ($type === 'quote' && ! $document)
                <x-ui.card title="Dossier de commande" icon="arrow-path-rounded-square">
                    <x-ui.toggle wire:model.live="openOrder" label="Ouvrir le suivi de commande" description="Le devis entre dans le parcours validation → facture → atelier / chantier." />
                    @if ($openOrder)
                        <div class="mt-4 space-y-3">
                            <x-ui.select label="Nature" wire:model="orderKind" :options="config('erp.order_kinds')" />
                            <x-ui.input label="Objet (facultatif)" wire:model="orderTitle" placeholder="Ex. : Mobilier salle de réunion" />
                            <x-ui.input label="Livraison souhaitée" type="date" wire:model="deliveryDate" />
                        </div>
                    @endif
                </x-ui.card>
            @endif
        </aside>
    </div>
</div>
