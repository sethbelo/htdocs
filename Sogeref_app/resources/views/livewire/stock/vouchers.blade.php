<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header title="Bons" eyebrow="Stock & achats" subtitle="Bons d’achat, de décaissement et de sortie de matériel, avec circuit d’approbation.">
        <x-slot:actions>
            @if (auth()->user()->canManage('vouchers'))
                <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                    <x-ui.button variant="accent" icon="plus" icon-right="chevron-down" x-on:click="open = !open">Nouveau bon</x-ui.button>
                    <div x-show="open" x-transition x-cloak class="card absolute right-0 z-30 mt-2 w-72 p-1.5 shadow-[var(--shadow-pop)]">
                        @foreach (config('erp.voucher_types') as $key => $t)
                            <button type="button" wire:click="create('{{ $key }}')" x-on:click="open = false" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left hover:bg-surface-2">
                                <span class="grid size-9 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300"><x-ui.icon :name="$t['icon']" class="size-[18px]" /></span>
                                <span><span class="block text-[13.5px] font-semibold">{{ $t['label'] }}</span><span class="block text-[11.5px] text-fg-subtle">Préfixe {{ $t['prefix'] }}</span></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. CIRCUIT ═══════════════════════════════════════════════════════ --}}
    <div class="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
        @foreach (config('erp.statuses.voucher') as $key => [$label, $tone])
            @php $row = $counts->get($key); @endphp
            <button type="button" wire:click="$set('status', '{{ $status === $key ? '' : $key }}')" @class(['card px-3 py-2.5 text-left transition hover:border-brand-200', 'ring-2 ring-sun-400' => $status === $key])>
                <x-ui.badge :tone="$tone" dot>{{ $label }}</x-ui.badge>
                <p class="mt-1.5 font-display text-[20px] leading-none font-semibold">{{ $row->c ?? 0 }}</p>
                <p class="mt-1 text-[11.5px] text-fg-subtle tabular-nums">{{ money($row->t ?? 0, 'USD', 0) }}</p>
            </button>
        @endforeach
    </div>

    {{-- ═══ 3. LISTE ═════════════════════════════════════════════════════════ --}}
    <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line p-3 lg:flex-row lg:items-center">
            <div class="segmented self-start">
                <button type="button" wire:click="$set('type', '')" aria-pressed="{{ $type === '' ? 'true' : 'false' }}">Tous</button>
                @foreach (config('erp.voucher_types') as $key => $t)
                    <button type="button" wire:click="$set('type', '{{ $key }}')" aria-pressed="{{ $type === $key ? 'true' : 'false' }}">{{ $t['short'] }}</button>
                @endforeach
            </div>
            <select wire:model.live="siteFilter" class="input lg:w-64"><option value="">Tous les chantiers</option>@foreach ($sites as $s)<option value="{{ $s->id }}">{{ $s->code }} · {{ $s->name }}</option>@endforeach</select>
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="N°, objet, bénéficiaire…" class="lg:ml-auto" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Bon</th><th>Type</th><th>Objet</th><th>Bénéficiaire / chantier</th><th>Demandé par</th><th>Statut</th><th class="num">Montant</th><th></th></tr></thead>
                <tbody>
                    @forelse ($vouchers as $v)
                        <tr wire:key="v-{{ $v->id }}" class="cursor-pointer" wire:click="show({{ $v->id }})">
                            <td><p class="font-semibold">{{ $v->number }}</p><p class="text-[12px] text-fg-subtle">{{ $v->voucher_date->format('d/m/Y') }}</p></td>
                            <td><span class="flex items-center gap-1.5 text-[13px]"><x-ui.icon :name="config('erp.voucher_types.'.$v->type.'.icon')" class="size-4 text-fg-subtle" />{{ config('erp.voucher_types.'.$v->type.'.short') }}</span></td>
                            <td class="max-w-[280px] truncate">{{ $v->purpose }}</td>
                            <td class="max-w-[200px]"><p class="truncate">{{ $v->beneficiaryName() }}</p>@if ($v->site)<p class="truncate text-[12px] text-fg-subtle">{{ $v->site->code }}</p>@endif</td>
                            <td class="text-fg-muted">{{ $v->requester?->name }}</td>
                            <td><x-ui.status map="voucher" :value="$v->status" /></td>
                            <td class="num font-semibold">{{ money($v->amount, $v->currency_code) }}</td>
                            <td class="text-right" x-on:click.stop><a href="{{ route('pdf.voucher', $v) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Imprimer"><x-ui.icon name="printer" class="size-4" /></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty icon="ticket" title="Aucun bon" text="Créez un bon d’achat, de décaissement ou de sortie de matériel." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vouchers->hasPages())<div class="border-t border-line px-4 py-3">{{ $vouchers->links('components.ui.pagination') }}</div>@endif
    </div>

    {{-- ═══ 4. FORMULAIRE ════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showForm" :title="($editing ? 'Modifier — ' : '').config('erp.voucher_types.'.$formType.'.label')" :icon="config('erp.voucher_types.'.$formType.'.icon')" size="xl">
        <div class="space-y-5">
            @unless ($editing)
                <div class="segmented">
                    @foreach (config('erp.voucher_types') as $key => $t)
                        <button type="button" wire:click="$set('formType', '{{ $key }}')" aria-pressed="{{ $formType === $key ? 'true' : 'false' }}">{{ $t['label'] }}</button>
                    @endforeach
                </div>
            @endunless
            <div class="grid gap-4 md:grid-cols-3">
                <x-ui.input label="Date" type="date" wire:model="voucherDate" required />
                <x-ui.input label="Objet" wire:model="purpose" class="md:col-span-2" required :placeholder="['achat' => 'Ex. : Réassort de tissu pour la commande CMD-…', 'decaissement' => 'Ex. : Carburant du groupe électrogène', 'sortie' => 'Ex. : Ciment et fer pour la dalle R+1'][$formType]" />
                <x-ui.select label="Chantier" wire:model="siteId" placeholder="Aucun" :options="$sites->mapWithKeys(fn ($s) => [$s->id => $s->code.' · '.$s->name])" />
                @if ($formType === 'achat')
                    <x-ui.select label="Fournisseur" wire:model="contactId" placeholder="Choisir…" :options="$suppliers->pluck('company_name', 'id')" required />
                    <x-ui.select label="Magasin de livraison" wire:model="warehouseId" placeholder="—" :options="$warehouses->pluck('name', 'id')" />
                @elseif ($formType === 'sortie')
                    <x-ui.select label="Magasin de sortie" wire:model="warehouseId" :options="$warehouses->pluck('name', 'id')" required />
                    <x-ui.input label="Remis à" wire:model="beneficiary" placeholder="Nom de la personne qui réceptionne" />
                @else
                    <x-ui.select label="Employé bénéficiaire" wire:model="employeeId" placeholder="—" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name])" />
                    <x-ui.input label="Ou bénéficiaire externe" wire:model="beneficiary" />
                    <x-ui.select label="Mode de paiement" wire:model="paymentMethod" :options="['cash' => 'Espèces', 'mobile_money' => 'Mobile money', 'bank_transfer' => 'Virement', 'cheque' => 'Chèque']" />
                @endif
            </div>

            <div>
                <p class="label">{{ $formType === 'decaissement' ? 'Détail de la dépense' : 'Articles' }}</p>
                <div class="table-wrap rounded-xl border border-line">
                    <table class="table min-w-[760px]">
                        <thead><tr><th class="w-[36%]">{{ $formType === 'decaissement' ? 'Désignation' : 'Article' }}</th><th class="w-24">Quantité</th><th class="w-24">Unité</th><th class="w-32">P.U.</th><th class="num w-28">Total</th><th class="w-10"></th></tr></thead>
                        <tbody>
                            @foreach ($lines as $i => $line)
                                <tr wire:key="vl-{{ $i }}" class="align-top">
                                    <td class="space-y-1.5">
                                        @if ($formType !== 'decaissement')
                                            <select wire:model.live="lines.{{ $i }}.product_id" @class(['input', 'input-error' => $errors->has("lines.$i.product_id")])>
                                                <option value="">{{ $formType === 'achat' ? '— Article libre —' : 'Choisir un article…' }}</option>
                                                @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->sku }} · {{ $p->name }}</option>@endforeach
                                            </select>
                                        @endif
                                        <input wire:model.blur="lines.{{ $i }}.description" @class(['input', 'input-error' => $errors->has("lines.$i.description")]) placeholder="Désignation">
                                    </td>
                                    <td><input type="number" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" class="input text-right"></td>
                                    <td><input list="voucher-units" wire:model.blur="lines.{{ $i }}.unit" class="input"></td>
                                    <td><input type="number" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.unit_price" class="input text-right"></td>
                                    <td class="num pt-4 font-semibold">{{ money((float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0)) }}</td>
                                    <td class="pt-3"><button type="button" wire:click="removeLine({{ $i }})" class="btn btn-ghost btn-sm btn-icon text-fg-subtle"><x-ui.icon name="trash" class="size-4" /></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <datalist id="voucher-units">@foreach (config('erp.units') as $u)<option value="{{ $u }}">@endforeach</datalist>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addLine">Ajouter une ligne</x-ui.button>
                    <p class="text-[14px]">Total : <strong class="font-display text-[18px]">{{ money(collect($lines)->sum(fn ($l) => (float) ($l['quantity'] ?? 0) * (float) ($l['unit_price'] ?? 0))) }}</strong></p>
                </div>
            </div>
            <x-ui.textarea label="Observations" wire:model="notes" rows="2" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="secondary" icon="document" wire:click="save">Enregistrer le brouillon</x-ui.button>
            <x-ui.button variant="primary" icon="paper-airplane" wire:click="saveAndSubmit">Soumettre pour approbation</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    {{-- ═══ 5. DÉTAIL & VALIDATION ═══════════════════════════════════════════ --}}
    @if ($viewingVoucher)
        @php $v = $viewingVoucher; @endphp
        <div x-data="{ open: true }" x-init="$watch('open', value => value || $wire.set('viewing', null))" x-on:keydown.escape.window="open = false">
            <template x-teleport="body">
                <div x-show="open" class="fixed inset-0 z-50">
                    <div class="overlay" x-on:click="open = false"></div>
                    <div class="drawer drawer-lg" x-transition>
                        <header class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
                            <div>
                                <p class="eyebrow">{{ $v->typeLabel() }}</p>
                                <h2 class="font-display text-[20px] font-semibold">{{ $v->number }}</h2>
                                <div class="mt-1 flex items-center gap-2"><x-ui.status map="voucher" :value="$v->status" /><span class="text-[12.5px] text-fg-subtle">{{ $v->voucher_date->format('d/m/Y') }}</span></div>
                            </div>
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" x-on:click="open = false"><x-ui.icon name="x-mark" /></button>
                        </header>
                        <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                            <div class="rounded-xl bg-surface-2 p-4">
                                <p class="font-semibold">{{ $v->purpose }}</p>
                                <dl class="mt-3 grid grid-cols-2 gap-3 text-[13px]">
                                    <div><dt class="text-fg-subtle">Bénéficiaire</dt><dd class="font-medium">{{ $v->beneficiaryName() }}</dd></div>
                                    <div><dt class="text-fg-subtle">Montant</dt><dd class="font-display text-[18px] font-semibold">{{ money($v->amount) }}</dd></div>
                                    @if ($v->site)<div><dt class="text-fg-subtle">Chantier</dt><dd class="font-medium">{{ $v->site->code }} · {{ $v->site->name }}</dd></div>@endif
                                    @if ($v->warehouse)<div><dt class="text-fg-subtle">Magasin</dt><dd class="font-medium">{{ $v->warehouse->name }}</dd></div>@endif
                                    @if ($v->contact)<div><dt class="text-fg-subtle">Fournisseur</dt><dd class="font-medium">{{ $v->contact->display_name }}</dd></div>@endif
                                    @if ($v->manufacturingOrder)<div><dt class="text-fg-subtle">Ordre de fabrication</dt><dd><a href="{{ route('manufacturing.show', $v->manufacturingOrder) }}" wire:navigate class="link">{{ $v->manufacturingOrder->number }}</a></dd></div>@endif
                                </dl>
                            </div>
                            <table class="table min-w-0">
                                <thead><tr><th>Désignation</th><th class="num">Qté</th>@if ($v->type === 'sortie')<th class="num">En stock</th>@endif<th class="num">Total</th></tr></thead>
                                <tbody>
                                    @foreach ($v->lines as $line)
                                        @php $available = (float) ($stock[$line->product_id] ?? 0); @endphp
                                        <tr>
                                            <td><p class="font-medium">{{ $line->description }}</p>@if ($line->product)<p class="text-[12px] text-fg-subtle">{{ $line->product->sku }}</p>@endif</td>
                                            <td class="num">{{ qty($line->quantity) }} {{ $line->unit }}</td>
                                            @if ($v->type === 'sortie')<td class="num {{ $available < (float) $line->quantity && $v->status !== 'executed' ? 'font-semibold text-flame-600' : '' }}">{{ qty($available) }}</td>@endif
                                            <td class="num font-semibold">{{ money($line->total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($v->notes)<p class="rounded-xl border border-line p-3 text-[13px] whitespace-pre-line text-fg-muted">{{ $v->notes }}</p>@endif
                            <ol class="space-y-2 text-[13px]">
                                <li class="flex items-center gap-2"><x-ui.icon name="pencil-square" class="size-4 text-fg-subtle" /> Demandé par <strong>{{ $v->requester?->name }}</strong> le {{ $v->created_at->format('d/m/Y H:i') }}</li>
                                @if ($v->approved_at)<li class="flex items-center gap-2"><x-ui.icon name="check-badge" class="size-4 text-fg-subtle" /> {{ $v->status === 'rejected' ? 'Rejeté' : 'Approuvé' }} par <strong>{{ $v->approver?->name }}</strong> le {{ $v->approved_at->format('d/m/Y H:i') }}</li>@endif
                                @if ($v->executed_at)<li class="flex items-center gap-2"><x-ui.icon name="check-circle" class="size-4 text-emerald-600" /> Exécuté par <strong>{{ $v->executor?->name }}</strong> le {{ $v->executed_at->format('d/m/Y H:i') }}</li>@endif
                            </ol>
                            @if ($v->status === 'approved' && $v->type === 'decaissement' && (auth()->user()->isMaster() || auth()->user()->can('vouchers.execute')))
                                <x-ui.select label="Payer depuis" wire:model="executeAccountId" :options="$accounts->pluck('name', 'id')" />
                            @endif
                        </div>
                        <footer class="flex flex-wrap justify-end gap-2 border-t border-line bg-surface-2 px-6 py-3.5">
                            <a href="{{ route('pdf.voucher', $v) }}" target="_blank" class="btn btn-secondary mr-auto"><x-ui.icon name="printer" class="size-[18px]" /> Imprimer</a>
                            @if (in_array($v->status, ['draft', 'rejected']) && auth()->user()->canManage('vouchers'))
                                <x-ui.button variant="ghost" icon="x-circle" wire:click="cancel">Annuler le bon</x-ui.button>
                                <x-ui.button variant="secondary" icon="pencil-square" wire:click="edit({{ $v->id }})">Modifier</x-ui.button>
                                <x-ui.button variant="primary" icon="paper-airplane" wire:click="submit">Soumettre</x-ui.button>
                            @endif
                            @if ($v->status === 'submitted' && (auth()->user()->isMaster() || auth()->user()->can('vouchers.approve')))
                                <x-ui.button variant="danger" icon="x-mark" wire:click="$set('showReject', true)">Rejeter</x-ui.button>
                                <x-ui.button variant="success" icon="check" wire:click="approve">Approuver</x-ui.button>
                            @endif
                            @if ($v->status === 'approved' && (auth()->user()->isMaster() || auth()->user()->can('vouchers.execute')))
                                <x-ui.button variant="primary" icon="bolt" wire:click="execute" wire:confirm="Exécuter ce bon ? Le stock ou la caisse seront mis à jour.">{{ ['sortie' => 'Remettre le matériel', 'decaissement' => 'Payer', 'achat' => 'Passer la commande'][$v->type] }}</x-ui.button>
                            @endif
                        </footer>
                    </div>
                </div>
            </template>
        </div>
    @endif

    <x-ui.modal wire:model="showReject" title="Rejeter le bon" icon="x-circle" tone="danger">
        <x-ui.textarea wire:model="rejectReason" rows="3" placeholder="Motif du rejet (visible par le demandeur)" />
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="danger" wire:click="reject">Rejeter</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
