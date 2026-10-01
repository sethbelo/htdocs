<div>
    <x-ui.page-header title="Mes bons" eyebrow="Espace personnel" subtitle="Émettez un bon de décaissement, d’achat ou de sortie de matériel et suivez sa signature jusqu’à l’exécution.">
        <x-slot:actions>
            <x-ui.button variant="accent" icon="plus" wire:click="create">Émettre un bon</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>


    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Brouillons" :value="$counts['draft']->c ?? 0" icon="pencil-square" tone="brand" />
        <x-ui.stat label="En attente de signature" :value="$counts['submitted']->c ?? 0" icon="clock" tone="sun" :hint="money($counts['submitted']->t ?? 0)" />
        <x-ui.stat label="Approuvés" :value="($counts['approved']->c ?? 0) + ($counts['executed']->c ?? 0)" icon="check-badge" tone="emerald" :hint="($counts['executed']->c ?? 0).' exécuté(s)'" />
        <x-ui.stat label="Rejetés" :value="$counts['rejected']->c ?? 0" icon="x-circle" tone="flame" hint="À corriger et resoumettre" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center gap-1 border-b border-line p-3">
            @foreach (['' => 'Tous', 'draft' => 'Brouillons', 'submitted' => 'À signer', 'approved' => 'Approuvés', 'executed' => 'Exécutés', 'rejected' => 'Rejetés'] as $key => $label)
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['btn btn-sm', 'btn-soft' => $status === $key, 'btn-ghost' => $status !== $key])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Bon</th><th>Objet</th><th>Date</th><th class="num">Montant</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @forelse ($vouchers as $v)
                        <tr wire:key="pv-{{ $v->id }}">
                            <td>
                                <button type="button" wire:click="show({{ $v->id }})" class="flex items-center gap-2.5 text-left">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-sun-100 text-sun-800 dark:bg-sun-400/15 dark:text-sun-300"><x-ui.icon :name="config('erp.voucher_types.'.$v->type.'.icon', 'ticket')" class="size-[18px]" /></span>
                                    <span><span class="block font-semibold text-brand-600 dark:text-lagoon-300">{{ $v->number }}</span><span class="text-[12px] text-fg-subtle">{{ config('erp.voucher_types.'.$v->type.'.short') }}</span></span>
                                </button>
                            </td>
                            <td class="max-w-[320px]"><p class="truncate">{{ $v->purpose }}</p>@if ($v->site)<p class="text-[12px] text-fg-subtle">{{ $v->site->code }} · {{ $v->site->name }}</p>@endif</td>
                            <td class="whitespace-nowrap text-fg-muted">{{ $v->voucher_date->format('d/m/Y') }}</td>
                            <td class="num font-semibold">{{ money($v->amount, $v->currency_code) }}</td>
                            <td><x-ui.status map="voucher" :value="$v->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                @if (in_array($v->status, ['draft', 'rejected']))
                                    <x-ui.button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $v->id }})">Modifier</x-ui.button>
                                    <x-ui.button variant="primary" size="sm" icon="paper-airplane" wire:click="submit({{ $v->id }})">Soumettre</x-ui.button>
                                @endif
                                <a href="{{ route('personal.vouchers.pdf', $v) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Imprimer" aria-label="Imprimer"><x-ui.icon name="printer" class="size-4" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="ticket" title="Aucun bon" text="Vos bons émis apparaîtront ici avec leur état de signature."><x-ui.button variant="primary" icon="plus" wire:click="create">Émettre un bon</x-ui.button></x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vouchers->hasPages())<div class="border-t border-line px-4 py-3">{{ $vouchers->links('components.ui.pagination') }}</div>@endif
    </div>

    {{-- ═══ FORMULAIRE ═══════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showForm" :title="($editing ? 'Modifier — ' : 'Émettre — ').config('erp.voucher_types.'.$formType.'.label')" :icon="config('erp.voucher_types.'.$formType.'.icon')" size="lg"
                 :subtitle="$employee ? 'Demandeur : '.$employee->full_name : 'Demandeur : '.auth()->user()->name">
        <div class="space-y-5">
            @unless ($editing)
                <div class="segmented flex-wrap">
                    @foreach (config('erp.voucher_types') as $key => $t)
                        <button type="button" wire:click="$set('formType', '{{ $key }}')" aria-pressed="{{ $formType === $key ? 'true' : 'false' }}">{{ $t['short'] }}</button>
                    @endforeach
                </div>
            @endunless
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input label="Date" type="date" wire:model="voucherDate" required />
                <x-ui.select label="Chantier (facultatif)" wire:model="siteId" placeholder="Aucun" :options="$sites->mapWithKeys(fn ($s) => [$s->id => $s->code.' · '.$s->name])" />
                <x-ui.input label="Objet" wire:model="purpose" class="md:col-span-2" required :placeholder="['achat' => 'Ex. : Fournitures de bureau', 'decaissement' => 'Ex. : Frais de mission à Kinkole', 'sortie' => 'Ex. : Outillage pour le chantier'][$formType] ?? ''" />
                @if ($formType === 'achat')
                    <x-ui.select label="Fournisseur" wire:model="contactId" placeholder="Choisir…" :options="$suppliers->pluck('company_name', 'id')" required />
                    <x-ui.select label="Magasin de livraison" wire:model="warehouseId" placeholder="—" :options="$warehouses->pluck('name', 'id')" />
                @elseif ($formType === 'sortie')
                    <x-ui.select label="Magasin de sortie" wire:model="warehouseId" placeholder="Choisir…" :options="$warehouses->pluck('name', 'id')" required />
                    <x-ui.input label="Remis à" wire:model="beneficiary" />
                @else
                    <x-ui.input label="Bénéficiaire" wire:model="beneficiary" />
                    <x-ui.select label="Mode de paiement souhaité" wire:model="paymentMethod" :options="['cash' => 'Espèces', 'mobile_money' => 'Mobile money', 'bank_transfer' => 'Virement', 'cheque' => 'Chèque']" />
                @endif
            </div>

            <div>
                <p class="label">{{ $formType === 'decaissement' ? 'Détail de la dépense' : 'Articles' }}</p>
                <div class="space-y-2">
                    @foreach ($lines as $i => $line)
                        <div wire:key="pvl-{{ $i }}" class="rounded-xl border border-line bg-surface-2 p-3">
                            <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                                <div class="space-y-2">
                                    @if ($formType !== 'decaissement')
                                        <select wire:model.live="lines.{{ $i }}.product_id" @class(['input', 'input-error' => $errors->has("lines.$i.product_id")]) aria-label="Article">
                                            <option value="">{{ $formType === 'achat' ? '— Article libre —' : 'Choisir un article…' }}</option>
                                            @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->sku }} · {{ $p->name }}</option>@endforeach
                                        </select>
                                    @endif
                                    <input wire:model.blur="lines.{{ $i }}.description" @class(['input', 'input-error' => $errors->has("lines.$i.description")]) placeholder="Désignation" aria-label="Désignation">
                                </div>
                                <button type="button" wire:click="removeLine({{ $i }})" class="btn btn-ghost btn-sm btn-icon justify-self-end text-fg-subtle" aria-label="Retirer la ligne"><x-ui.icon name="trash" class="size-4" /></button>
                            </div>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" @class(['input text-right', 'input-error' => $errors->has("lines.$i.quantity")]) aria-label="Quantité" placeholder="Qté">
                                <input list="pv-units" wire:model.blur="lines.{{ $i }}.unit" class="input" aria-label="Unité" placeholder="Unité">
                                <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="lines.{{ $i }}.unit_price" class="input text-right" aria-label="Prix unitaire" placeholder="P.U.">
                            </div>
                            <p class="mt-1.5 text-right text-[12.5px] text-fg-subtle">Sous-total : <strong class="text-fg">{{ money((float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0)) }}</strong></p>
                        </div>
                    @endforeach
                    <datalist id="pv-units">@foreach (config('erp.units') as $u)<option value="{{ $u }}">@endforeach</datalist>
                </div>
                @error('lines')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-2 flex items-center justify-between">
                    <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addLine">Ajouter une ligne</x-ui.button>
                    <p class="text-[14px]">Total : <strong class="font-display text-[18px]">{{ money(collect($lines)->sum(fn ($l) => (float) ($l['quantity'] ?? 0) * (float) ($l['unit_price'] ?? 0))) }}</strong></p>
                </div>
            </div>
            <x-ui.textarea label="Observations" wire:model="notes" rows="2" />
            <p class="flex items-start gap-2 rounded-xl bg-lagoon-50 p-3 text-[12.5px] text-lagoon-800 dark:bg-lagoon-400/10 dark:text-lagoon-200"><x-ui.icon name="information-circle" class="size-4" />Une fois soumis, le bon est transmis à la direction pour signature, puis exécuté par la caisse ou le magasin.</p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="secondary" icon="document-check" wire:click="save">Brouillon</x-ui.button>
            <x-ui.button variant="primary" icon="paper-airplane" wire:click="saveAndSubmit">Soumettre pour signature</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    {{-- ═══ DÉTAIL ═══════════════════════════════════════════════════════════ --}}
    @if ($viewingVoucher)
        @php $v = $viewingVoucher; @endphp
        <div wire:key="pv-detail-{{ $v->id }}" x-data="{ open: true }" x-init="$watch('open', value => value || $wire.set('viewing', null))" x-on:keydown.escape.window="open = false">
            <template x-teleport="body">
                <div x-show="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
                    <div class="overlay" x-on:click="open = false"></div>
                    <div class="drawer drawer-lg" x-transition>
                        <header class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
                            <div>
                                <p class="eyebrow">{{ $v->typeLabel() }}</p>
                                <h2 class="font-display text-[20px] font-semibold">{{ $v->number }}</h2>
                                <div class="mt-1 flex items-center gap-2"><x-ui.status map="voucher" :value="$v->status" /><span class="text-[12.5px] text-fg-subtle">{{ $v->voucher_date->format('d/m/Y') }}</span></div>
                            </div>
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" x-on:click="open = false" aria-label="Fermer"><x-ui.icon name="x-mark" /></button>
                        </header>
                        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                            <x-ui.stepper :steps="['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Signé', 'executed' => 'Exécuté']" :current="$v->status === 'rejected' ? 'draft' : $v->status" :cancelled="$v->status === 'cancelled'" />
                            <div class="rounded-xl bg-surface-2 p-4">
                                <p class="font-semibold">{{ $v->purpose }}</p>
                                <dl class="mt-3 grid grid-cols-2 gap-3 text-[13px]">
                                    <div><dt class="text-fg-subtle">Bénéficiaire</dt><dd class="font-medium">{{ $v->beneficiaryName() }}</dd></div>
                                    <div><dt class="text-fg-subtle">Montant</dt><dd class="font-display text-[18px] font-semibold">{{ money($v->amount, $v->currency_code) }}</dd></div>
                                    @if ($v->site)<div><dt class="text-fg-subtle">Chantier</dt><dd class="font-medium">{{ $v->site->code }} · {{ $v->site->name }}</dd></div>@endif
                                    @if ($v->warehouse)<div><dt class="text-fg-subtle">Magasin</dt><dd class="font-medium">{{ $v->warehouse->name }}</dd></div>@endif
                                    @if ($v->contact)<div><dt class="text-fg-subtle">Fournisseur</dt><dd class="font-medium">{{ $v->contact->display_name }}</dd></div>@endif
                                </dl>
                            </div>
                            <table class="table min-w-0">
                                <thead><tr><th>Désignation</th><th class="num">Qté</th><th class="num">Total</th></tr></thead>
                                <tbody>
                                    @foreach ($v->lines as $line)
                                        <tr><td><p class="font-medium">{{ $line->description }}</p>@if ($line->product)<p class="text-[12px] text-fg-subtle">{{ $line->product->sku }}</p>@endif</td><td class="num">{{ qty($line->quantity) }} {{ $line->unit }}</td><td class="num font-semibold">{{ money($line->total) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($v->notes)<p class="rounded-xl border border-line p-3 text-[13px] whitespace-pre-line text-fg-muted">{{ $v->notes }}</p>@endif
                            <ol class="space-y-2 text-[13px]">
                                <li class="flex items-center gap-2"><x-ui.icon name="pencil-square" class="size-4 text-fg-subtle" /> Émis le {{ $v->created_at->format('d/m/Y H:i') }}</li>
                                @if ($v->approved_at)<li class="flex items-center gap-2"><x-ui.icon name="check-badge" class="size-4 text-fg-subtle" /> {{ $v->status === 'rejected' ? 'Rejeté' : 'Signé' }} par <strong>{{ $v->approver?->name }}</strong> le {{ $v->approved_at->format('d/m/Y H:i') }}</li>@endif
                                @if ($v->executed_at)<li class="flex items-center gap-2"><x-ui.icon name="check-circle" class="size-4 text-emerald-600" /> Exécuté par <strong>{{ $v->executor?->name }}</strong> le {{ $v->executed_at->format('d/m/Y H:i') }}</li>@endif
                            </ol>
                        </div>
                        <footer class="flex flex-wrap justify-end gap-2 border-t border-line bg-surface-2 px-6 py-3.5">
                            <a href="{{ route('personal.vouchers.pdf', $v) }}" target="_blank" class="btn btn-secondary mr-auto"><x-ui.icon name="printer" class="size-[18px]" /> Imprimer</a>
                            @if (in_array($v->status, ['draft', 'submitted', 'rejected']))
                                <x-ui.button variant="ghost" icon="x-circle" wire:click="cancel({{ $v->id }})" wire:confirm="Annuler ce bon ?">Annuler le bon</x-ui.button>
                            @endif
                            @if (in_array($v->status, ['draft', 'rejected']))
                                <x-ui.button variant="secondary" icon="pencil-square" wire:click="edit({{ $v->id }})">Modifier</x-ui.button>
                                <x-ui.button variant="primary" icon="paper-airplane" wire:click="submit({{ $v->id }})">Soumettre</x-ui.button>
                            @endif
                        </footer>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
