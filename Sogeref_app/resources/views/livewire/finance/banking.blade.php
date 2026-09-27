<div>
    @php $canManage = auth()->user()->canManage('banking'); @endphp
    <x-ui.page-header title="Trésorerie" eyebrow="Finance" subtitle="Banques, caisses et mobile money : soldes, mouvements, virements internes et rapprochement.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="arrows-right-left" wire:click="newMove('transfer')">Virement interne</x-ui.button>
                <x-ui.button variant="secondary" icon="minus-circle" wire:click="newMove('fee')">Frais bancaires</x-ui.button>
                <x-ui.button variant="secondary" icon="receipt-refund" wire:click="newExpense">Dépense</x-ui.button>
                <x-ui.button variant="accent" icon="plus" wire:click="editAccount">Compte</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($accounts as $a)
            <button type="button" wire:click="$set('accountId', {{ $a->id }})" @class(['card p-4 text-left transition hover:border-brand-200', 'ring-2 ring-sun-400' => $accountId === $a->id, 'opacity-60' => ! $a->is_active])>
                <div class="flex items-center justify-between"><x-ui.icon :name="['bank' => 'building-library', 'cash' => 'banknotes', 'mobile_money' => 'device-phone-mobile'][$a->type]" class="text-fg-subtle" />@if ($canManage)<span wire:click.stop="editAccount({{ $a->id }})" class="btn btn-ghost btn-sm btn-icon -mr-2 -mt-1"><x-ui.icon name="pencil-square" class="size-4" /></span>@endif</div>
                <p class="mt-2 truncate text-[12.5px] font-medium text-fg-muted">{{ $a->name }}</p>
                <p @class(['font-display text-[22px] font-semibold tabular-nums', 'text-flame-600' => $a->balance < 0])>{{ money($a->balance, $a->currency_code, 0) }}</p>
                <p class="truncate text-[11.5px] text-fg-subtle">{{ $a->provider }} · {{ $a->account_number }}</p>
            </button>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Flux de trésorerie" subtitle="Tous comptes, 6 mois" icon="chart-bar" class="xl:col-span-1">
            <div class="h-64" x-data="erpChart(@js($chart))"><canvas x-ref="canvas"></canvas></div>
            <p class="mt-3 text-[13px] text-fg-muted">Trésorerie totale : <strong class="text-fg">{{ money($accounts->where('is_active', true)->sum(fn ($a) => $a->balance)) }}</strong></p>
        </x-ui.card>

        <div class="card overflow-hidden xl:col-span-2">
            <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['mouvements' => ['label' => 'Mouvements du compte', 'icon' => 'queue-list'], 'depenses' => ['label' => 'Dépenses générales', 'icon' => 'receipt-refund']]" /></div>
            @if ($tab === 'mouvements')
                <div class="flex items-center justify-between gap-2 border-b border-line p-3">
                    <p class="font-display text-[15px] font-semibold">{{ $current?->name }}</p>
                    <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Libellé ou référence…" />
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th>Libellé</th><th>Référence</th><th class="num">Entrée</th><th class="num">Sortie</th><th class="text-center">Rappr.</th></tr></thead>
                        <tbody>
                            @forelse ($transactions as $t)
                                <tr>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $t->transaction_date->format('d/m/Y') }}</td>
                                    <td class="max-w-[320px] truncate">{{ $t->description }}</td>
                                    <td class="text-[12.5px] text-fg-muted">{{ $t->reference }}</td>
                                    <td class="num font-semibold text-emerald-600">{{ $t->amount > 0 ? money($t->amount) : '' }}</td>
                                    <td class="num font-semibold text-flame-600">{{ $t->amount < 0 ? money(abs($t->amount)) : '' }}</td>
                                    <td class="text-center"><button type="button" @if ($canManage) wire:click="toggleReconciled({{ $t->id }})" @endif @class(['grid size-7 place-items-center rounded-full mx-auto', 'bg-emerald-500 text-white' => $t->status === 'reconciled', 'border border-line-strong text-fg-subtle' => $t->status !== 'reconciled'])><x-ui.icon name="check" class="size-4" /></button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="building-library" title="Aucun mouvement" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transactions->hasPages())<div class="border-t border-line px-4 py-3">{{ $transactions->links('components.ui.pagination') }}</div>@endif
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th>Dépense</th><th>Compte de charge</th><th>Fournisseur</th><th>Payé par</th><th class="num">Montant</th></tr></thead>
                        <tbody>
                            @forelse ($expenses as $e)
                                <tr>
                                    <td class="whitespace-nowrap text-fg-muted">{{ $e->expense_date->format('d/m/Y') }}</td>
                                    <td><p class="font-medium">{{ $e->description }}</p><p class="text-[12px] text-fg-subtle">{{ $e->reference }}</p></td>
                                    <td class="text-[12.5px]"><span class="font-semibold text-brand-600 dark:text-lagoon-300">{{ $e->account_code }}</span> {{ config('erp.expense_accounts.'.$e->account_code) }}</td>
                                    <td class="text-fg-muted">{{ $e->supplier?->company_name ?? '—' }}</td>
                                    <td class="text-fg-muted">{{ $e->financialAccount?->name }}</td>
                                    <td class="num font-semibold">{{ money($e->amount, $e->currency_code) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="receipt-refund" title="Aucune dépense" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($expenses->hasPages())<div class="border-t border-line px-4 py-3">{{ $expenses->links('components.ui.pagination') }}</div>@endif
            @endif
        </div>
    </div>

    <x-ui.drawer wire:model="showAccount" title="Compte de trésorerie" icon="building-library">
        <div class="space-y-4">
            <x-ui.select label="Type" wire:model="account.type" :options="['bank' => 'Banque', 'cash' => 'Caisse', 'mobile_money' => 'Mobile money']" />
            <x-ui.input label="Nom" wire:model="account.name" required />
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Établissement" wire:model="account.provider" /><x-ui.input label="N° de compte" wire:model="account.account_number" /></div>
            <div class="grid grid-cols-2 gap-3"><x-ui.select label="Devise" wire:model="account.currency_code" :options="['USD' => 'USD', 'CDF' => 'CDF', 'EUR' => 'EUR']" /><x-ui.input label="Solde d’ouverture" type="number" step="0.01" wire:model="account.opening_balance" /></div>
            <x-ui.toggle wire:model="account.is_active" label="Actif" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveAccount">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showExpense" title="Dépense générale" subtitle="Loyer, électricité, assurance, entretien…" icon="receipt-refund">
        <div class="space-y-4">
            <x-ui.input label="Libellé" wire:model="expense.description" required />
            <x-ui.select label="Compte de charge" wire:model="expense.account_code" :options="collect(config('erp.expense_accounts'))->mapWithKeys(fn ($l, $c) => [$c => $c.' · '.$l])" />
            <x-ui.select label="Fournisseur" wire:model="expense.supplier_id" placeholder="—" :options="$suppliers->pluck('company_name', 'id')" />
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Montant TTC" type="number" step="0.01" wire:model="expense.amount" suffix="USD" /><x-ui.input label="Dont TVA" type="number" step="0.01" wire:model="expense.tax_amount" suffix="USD" /></div>
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Date" type="date" wire:model="expense.expense_date" /><x-ui.select label="Payé depuis" wire:model="expense.financial_account_id" :options="$accounts->pluck('name', 'id')" /></div>
            <x-ui.textarea label="Notes" wire:model="expense.notes" rows="2" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveExpense">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showMove" :title="['transfer' => 'Virement interne', 'fee' => 'Frais bancaires', 'in' => 'Apport de fonds'][$move['kind'] ?? 'transfer']" icon="arrows-right-left">
        <div class="space-y-4">
            <x-ui.select :label="($move['kind'] ?? '') === 'transfer' ? 'Depuis' : 'Compte'" wire:model="move.from" :options="$accounts->pluck('name', 'id')" />
            @if (($move['kind'] ?? '') === 'transfer')<x-ui.select label="Vers" wire:model="move.to" placeholder="Choisir…" :options="$accounts->pluck('name', 'id')" />@endif
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Date" type="date" wire:model="move.date" /><x-ui.input label="Montant" type="number" step="0.01" wire:model="move.amount" suffix="USD" /></div>
            <x-ui.input label="Libellé" wire:model="move.label" />
            <x-ui.input label="Référence" wire:model="move.reference" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveMove">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
