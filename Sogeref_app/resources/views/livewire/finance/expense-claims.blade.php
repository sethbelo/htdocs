<div>
    <x-ui.page-header title="Notes de frais" eyebrow="Finance" subtitle="Dépenses engagées par le personnel (transport, repas, chantier…), avec justificatifs, approbation et remboursement.">
        <x-slot:actions><x-ui.button variant="accent" icon="plus" wire:click="create">Nouvelle note</x-ui.button></x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-5">
        @foreach (config('erp.statuses.expense_claim') as $k => [$l, $tone])
            @php $row = $totals->get($k); @endphp
            <button type="button" wire:click="$set('status', '{{ $status === $k ? '' : $k }}')" @class(['card px-3 py-2.5 text-left', 'ring-2 ring-sun-400' => $status === $k])>
                <x-ui.badge :tone="$tone" dot>{{ $l }}</x-ui.badge>
                <p class="mt-1.5 font-display text-[19px] leading-none font-semibold">{{ $row->c ?? 0 }}</p>
                <p class="mt-1 text-[11.5px] text-fg-subtle">{{ money($row->t ?? 0, 'USD', 0) }}</p>
            </button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Note</th><th>Collaborateur</th><th>Chantier</th><th>Date</th><th>Statut</th><th class="num">Montant</th></tr></thead>
                <tbody>
                    @forelse ($claims as $c)
                        <tr class="cursor-pointer" wire:click="show({{ $c->id }})">
                            <td><p class="font-semibold">{{ $c->number }}</p><p class="max-w-[260px] truncate text-[12px] text-fg-subtle">{{ $c->title }}</p></td>
                            <td>{{ $c->employee?->full_name ?? $c->user?->name }}</td>
                            <td class="text-fg-muted">{{ $c->site?->code ?? '—' }}</td>
                            <td class="text-fg-muted">{{ $c->created_at->format('d/m/Y') }}</td>
                            <td><x-ui.status map="expense_claim" :value="$c->status" /></td>
                            <td class="num font-semibold">{{ money($c->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="receipt-percent" title="Aucune note de frais" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-ui.drawer wire:model="showForm" :title="$editing ? 'Modifier la note' : 'Nouvelle note de frais'" icon="receipt-percent" size="xl">
        <div class="space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <x-ui.input label="Intitulé" wire:model="title" class="md:col-span-3" required placeholder="Ex. : Déplacements chantier — semaine 39" />
                <x-ui.select label="Collaborateur" wire:model="employeeId" placeholder="—" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name])" />
                <x-ui.select label="Chantier (facultatif)" wire:model="siteId" placeholder="—" :options="$sites->mapWithKeys(fn ($s) => [$s->id => $s->code.' · '.$s->name])" />
            </div>
            <div class="table-wrap rounded-xl border border-line">
                <table class="table min-w-[820px]">
                    <thead><tr><th class="w-36">Date</th><th class="w-44">Catégorie</th><th>Description</th><th class="w-32">Montant</th><th class="w-48">Justificatif</th><th class="w-10"></th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr wire:key="el-{{ $i }}">
                                <td><input type="date" wire:model="lines.{{ $i }}.spent_at" class="input"></td>
                                <td><select wire:model="lines.{{ $i }}.category" class="input">@foreach (config('erp.expense_categories') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></td>
                                <td><input wire:model="lines.{{ $i }}.description" @class(['input', 'input-error' => $errors->has("lines.$i.description")])></td>
                                <td><input type="number" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.amount" @class(['input text-right', 'input-error' => $errors->has("lines.$i.amount")])></td>
                                <td>
                                    <input type="file" wire:model="receipts.{{ $i }}" accept="image/*,application/pdf" capture="environment" class="block w-full text-[12px] file:mr-2 file:rounded-md file:border-0 file:bg-brand-50 file:px-2 file:py-1.5 file:text-[12px] file:font-semibold file:text-brand-700">
                                    @if (! empty($line['receipt_path']))<a href="{{ asset('storage/'.$line['receipt_path']) }}" target="_blank" class="text-[11.5px] link">Voir le justificatif actuel</a>@endif
                                </td>
                                <td><button type="button" wire:click="removeLine({{ $i }})" class="btn btn-ghost btn-sm btn-icon text-fg-subtle"><x-ui.icon name="trash" class="size-4" /></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between">
                <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addLine">Ajouter une dépense</x-ui.button>
                <p>Total : <strong class="font-display text-[18px]">{{ money(collect($lines)->sum(fn ($l) => (float) ($l['amount'] ?: 0))) }}</strong></p>
            </div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="secondary" icon="document" wire:click="save">Enregistrer</x-ui.button>
            <x-ui.button variant="primary" icon="paper-airplane" wire:click="saveAndSubmit">Soumettre</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    @if ($viewingClaim)
        @php $c = $viewingClaim; @endphp
        <div x-data="{ open: true }" x-init="$watch('open', v => v || $wire.set('viewing', null))">
            <template x-teleport="body">
                <div x-show="open" class="fixed inset-0 z-50">
                    <div class="overlay" x-on:click="open = false"></div>
                    <div class="drawer drawer-lg" x-transition>
                        <header class="flex items-start justify-between border-b border-line px-6 py-4">
                            <div><p class="eyebrow">{{ $c->number }}</p><h2 class="font-display text-[19px] font-semibold">{{ $c->title }}</h2><div class="mt-1 flex items-center gap-2"><x-ui.status map="expense_claim" :value="$c->status" /><span class="text-[12.5px] text-fg-subtle">{{ $c->employee?->full_name ?? $c->user?->name }}</span></div></div>
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" x-on:click="open = false"><x-ui.icon name="x-mark" /></button>
                        </header>
                        <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                            <table class="table min-w-0">
                                <thead><tr><th>Date</th><th>Catégorie</th><th>Description</th><th class="num">Montant</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($c->lines as $l)
                                        <tr><td class="text-fg-muted">{{ $l->spent_at->format('d/m') }}</td><td>{{ config('erp.expense_categories.'.$l->category) }}</td><td>{{ $l->description }}</td><td class="num font-semibold">{{ money($l->amount) }}</td><td>@if ($l->receipt_path)<a href="{{ asset('storage/'.$l->receipt_path) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Justificatif"><x-ui.icon name="paper-clip" class="size-4" /></a>@else<span class="text-[11px] text-flame-600">sans justificatif</span>@endif</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-right text-[15px]">Total : <strong class="font-display text-[20px]">{{ money($c->total) }}</strong></p>
                            @if ($c->comment)<p class="rounded-xl bg-surface-2 p-3 text-[13px]"><strong>Commentaire :</strong> {{ $c->comment }}</p>@endif
                            @if ($c->status === 'submitted' && (auth()->user()->isMaster() || auth()->user()->can('expense-claims.approve')))<x-ui.textarea label="Commentaire de décision" wire:model="comment" rows="2" />@endif
                            @if ($c->status === 'approved' && auth()->user()->canManage('expense-claims'))<x-ui.select label="Rembourser depuis" wire:model="accountId" :options="$accounts->pluck('name', 'id')" />@endif
                        </div>
                        <footer class="flex flex-wrap justify-end gap-2 border-t border-line bg-surface-2 px-6 py-3.5">
                            @if (in_array($c->status, ['draft', 'rejected']) && ($c->user_id === auth()->id() || auth()->user()->canManage('expense-claims')))
                                <x-ui.button variant="secondary" icon="pencil-square" wire:click="edit({{ $c->id }})">Modifier</x-ui.button>
                                <x-ui.button variant="primary" icon="paper-airplane" wire:click="submit">Soumettre</x-ui.button>
                            @endif
                            @if ($c->status === 'submitted' && (auth()->user()->isMaster() || auth()->user()->can('expense-claims.approve')))
                                <x-ui.button variant="danger" icon="x-mark" wire:click="decide(false)">Refuser</x-ui.button>
                                <x-ui.button variant="success" icon="check" wire:click="decide(true)">Approuver</x-ui.button>
                            @endif
                            @if ($c->status === 'approved' && auth()->user()->canManage('expense-claims'))
                                <x-ui.button variant="primary" icon="banknotes" wire:click="reimburse" wire:confirm="Rembourser {{ money($c->total) }} ?">Rembourser</x-ui.button>
                            @endif
                        </footer>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
