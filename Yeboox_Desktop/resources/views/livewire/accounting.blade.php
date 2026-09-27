@php
    $summaryCards = [
        ['Chiffre d’affaires', $revenue, 'Produits de la période', 'arrow-trending-up', 'emerald'],
        ['Dépenses', $expensesTotal, 'Charges comptabilisées', 'arrow-trending-down', 'rose'],
        ['Créances clients', $receivables, 'Montants à recouvrer', 'clock', 'amber'],
        ['Trésorerie nette', $cash, 'Comptes de classe 5', 'banknotes', 'violet'],
    ];
    $accountingTabs = [
        'overview' => ['Tableau de bord', 'squares-2x2', 'Synthèse financière et activité récente'],
        'invoices' => ['Factures', 'document-text', 'Documents clients et soldes ouverts'],
        'credits' => ['Avoirs', 'arrow-uturn-left', 'Corrections et annulations comptables'],
        'payments' => ['Paiements', 'banknotes', 'Encaissements clients enregistrés'],
        'expenses' => ['Dépenses', 'receipt-percent', 'Charges et justificatifs fournisseurs'],
        'customers' => ['Clients', 'users', 'Comptes tiers et activité commerciale'],
        'products' => ['Produits', 'cube', 'Catalogue et comptes de produits'],
        'journal' => ['Journal OHADA', 'book-open', 'Écritures équilibrées de la période'],
    ];
    $currentSection = $accountingTabs[$activeTab] ?? $accountingTabs['overview'];
@endphp

<div class="space-y-6">
    <div x-data="{ show: false, message: '' }" x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3200)" x-show="show" x-transition class="fixed right-5 top-5 z-50" style="display:none">
        <flux:callout variant="success" icon="check-circle" x-text="message" />
    </div>

    {{-- En-tête partagé : pilotage comptable conforme au référentiel SYSCOHADA. --}}
    <x-page-hero
        title="Comptabilité"
        eyebrow="SYSCOHADA · RDC"
        description="Pilotez les produits, les charges, les créances et la trésorerie depuis un registre unifié."
        icon="scale"
        badge-icon="building-library"
        illustration="accounting"
    >
        <x-slot:actions>
            <flux:button wire:click="$set('showExpenseModal', true)" variant="filled" icon="receipt-percent" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!">Dépense</flux:button>
            <flux:button wire:click="$set('showPaymentModal', true)" variant="filled" icon="banknotes" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!">Paiement</flux:button>
            <flux:button href="{{ route('invoices') }}" wire:navigate variant="primary" color="zinc" icon="document-plus" class="bg-white! text-indigo-900! hover:bg-violet-50!">Nouvelle facture</flux:button>
        </x-slot:actions>
    </x-page-hero>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Indicateurs comptables">
        @foreach($summaryCards as [$label, $value, $description, $icon, $tone])
            <flux:card class="p-4! sm:p-5!">
                <div class="flex items-center justify-between gap-3"><flux:text size="sm">{{ $label }}</flux:text><span @class(['grid size-9 shrink-0 place-items-center rounded-xl', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' => $tone === 'emerald', 'bg-rose-50 text-rose-600 dark:bg-rose-500/10' => $tone === 'rose', 'bg-amber-50 text-amber-600 dark:bg-amber-500/10' => $tone === 'amber', 'bg-violet-50 text-violet-600 dark:bg-violet-500/10' => $tone === 'violet'])><flux:icon :name="$icon" class="size-4" /></span></div>
                <div class="mt-4 truncate text-xl font-semibold tracking-tight text-zinc-950 dark:text-white sm:text-2xl"><x-money :amount="$value" /></div><flux:text size="sm" class="mt-1">{{ $description }}</flux:text>
            </flux:card>
        @endforeach
    </section>

    <flux:card class="overflow-hidden p-0!">
        <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800"><span class="grid size-9 place-items-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300"><flux:icon :name="$currentSection[1]" class="size-4" /></span><div><flux:heading size="sm">{{ $currentSection[0] }}</flux:heading><flux:text size="sm">{{ $currentSection[2] }}</flux:text></div></div>
        <div class="p-2">
            <div class="grid grid-cols-2 gap-1 sm:grid-cols-4 xl:grid-cols-8" role="tablist" aria-label="Sections comptables">
                @foreach($accountingTabs as $key => [$label, $icon, $description])
                    <flux:button
                        wire:key="accounting-tab-{{ $key }}"
                        wire:click="setTab('{{ $key }}')"
                        variant="{{ $activeTab === $key ? 'primary' : 'ghost' }}"
                        color="{{ $activeTab === $key ? 'violet' : 'zinc' }}"
                        :icon="$icon"
                        role="tab"
                        aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                        aria-controls="accounting-panel-{{ $key }}"
                        class="w-full justify-start! transition-all xl:justify-center! {{ $activeTab === $key ? 'shadow-sm ring-1 ring-violet-400/30' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                    >{{ $label }}</flux:button>
                @endforeach
            </div>
        </div>
    </flux:card>

    @if($activeTab === 'overview')
        <div class="grid min-w-0 gap-5 xl:grid-cols-[1.35fr_.65fr]">
            <section class="min-w-0 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between"><div><flux:heading size="lg">Activité récente</flux:heading><flux:text size="sm" class="mt-1">Dernières écritures générées par les opérations.</flux:text></div><flux:button wire:click="setTab('journal')" variant="ghost" icon:trailing="arrow-right">Voir le journal</flux:button></div>
                <div class="mt-5 space-y-2">
                    @forelse($records->take(7) as $record)
                        <div class="flex items-center gap-3 rounded-xl border border-zinc-100 p-3 dark:border-zinc-800">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-xs font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $record->journal_code }}</span>
                            <div class="min-w-0 flex-1"><div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $record->description }}</div><flux:text size="sm">{{ $record->reference }} · compte {{ $record->syscohada_code }}</flux:text></div>
                            <div class="text-right text-sm font-semibold {{ $record->debit > 0 ? 'text-zinc-900 dark:text-white' : 'text-emerald-600' }}"><x-money :amount="$record->debit > 0 ? $record->debit : $record->credit" /><div class="text-xs font-normal text-zinc-500">{{ $record->debit > 0 ? 'Débit' : 'Crédit' }}</div></div>
                        </div>
                    @empty
                        <flux:callout icon="information-circle" heading="Aucune écriture">Validez une facture ou saisissez une dépense pour commencer.</flux:callout>
                    @endforelse
                </div>
            </section>
            <aside class="min-w-0 space-y-5">
                <div class="rounded-2xl bg-violet-600 p-5 text-white shadow-lg shadow-violet-900/10">
                    <flux:heading size="lg" class="text-white!">Actions rapides</flux:heading>
                    <p class="mt-1 text-sm text-violet-100">Les documents validés créent automatiquement leurs écritures équilibrées.</p>
                    <div class="mt-5 grid gap-2"><flux:button wire:click="$set('showCreditModal', true)" icon="arrow-uturn-left">Créer un avoir</flux:button><flux:button wire:click="$set('showEntryModal', true)" icon="pencil-square">Écriture diverse</flux:button></div>
                </div>
                <flux:callout icon="shield-check" heading="Contrôle OHADA">Journaux VE, AV, TR, AC et OD. Les pièces validées ne sont pas supprimées : elles sont corrigées par avoir ou contrepassation.</flux:callout>
            </aside>
        </div>
    @else
        <flux:card id="accounting-panel-{{ $activeTab }}" role="tabpanel" class="overflow-hidden p-0!">
            <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 dark:border-zinc-800 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex min-w-0 flex-1 items-end gap-4">
                    <span class="mb-0.5 hidden size-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 sm:grid"><flux:icon :name="$currentSection[1]" class="size-5" /></span>
                    <div class="w-full max-w-md"><flux:input wire:model.live.debounce.300ms="search" label="Rechercher dans {{ strtolower($currentSection[0]) }}" icon="magnifying-glass" placeholder="Référence, client ou libellé…" clearable /></div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($activeTab === 'credits')<flux:button wire:click="$set('showCreditModal', true)" variant="primary" color="violet" icon="plus">Nouvel avoir</flux:button>@endif
                    @if($activeTab === 'payments')<flux:button wire:click="$set('showPaymentModal', true)" variant="primary" color="violet" icon="plus">Nouveau paiement</flux:button>@endif
                    @if($activeTab === 'expenses')<flux:button wire:click="$set('showExpenseModal', true)" variant="primary" color="violet" icon="plus">Nouvelle dépense</flux:button>@endif
                    @if($activeTab === 'journal')<flux:button wire:click="$set('showEntryModal', true)" variant="primary" color="violet" icon="plus">Écriture diverse</flux:button>@endif
                </div>
            </div>

            <div class="overflow-x-auto [&_table]:min-w-full [&_th]:px-5! [&_th]:py-4! [&_td]:px-5! [&_td]:py-4! [&_tbody_tr]:transition-colors [&_tbody_tr:hover]:bg-zinc-50 dark:[&_tbody_tr:hover]:bg-zinc-800/40">
                @if($activeTab === 'invoices')
                    <flux:table><flux:table.columns><flux:table.column><x-table-heading icon="document-text">Facture</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="calendar-days">Date</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="signal">Statut</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">Total</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="calculator">Solde</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                        @forelse($invoices as $invoice)<flux:table.row><flux:table.cell><a href="{{ route('invoices') }}" wire:navigate class="font-semibold text-violet-600 hover:underline">{{ $invoice->number }}</a></flux:table.cell><flux:table.cell>{{ $invoice->customer->name ?? 'Client comptant' }}</flux:table.cell><flux:table.cell>{{ $invoice->invoice_date->format('d/m/Y') }}</flux:table.cell><flux:table.cell><flux:badge :color="$invoice->status === 'paid' ? 'green' : ($invoice->status === 'draft' ? 'zinc' : 'amber')">{{ $invoice->status === 'paid' ? 'Payée' : ($invoice->status === 'draft' ? 'Brouillon' : 'À payer') }}</flux:badge></flux:table.cell><flux:table.cell align="end"><x-money :amount="$invoice->total_amount" /></flux:table.cell><flux:table.cell align="end" class="font-semibold"><x-money :amount="$invoice->balance_due" /></flux:table.cell></flux:table.row>
                        @empty <flux:table.row><flux:table.cell colspan="6" class="py-12! text-center"><x-table-empty icon="document-text" title="Aucune facture" description="Les factures validées apparaîtront dans ce registre." /></flux:table.cell></flux:table.row>@endforelse
                    </flux:table.rows></flux:table>
                @elseif($activeTab === 'credits')
                    <flux:table><flux:table.columns><flux:table.column><x-table-heading icon="arrow-uturn-left">Avoir</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="document-text">Facture</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="chat-bubble-bottom-center-text">Motif</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="calendar-days">Date</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">Montant</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                        @forelse($creditNotes as $credit)<flux:table.row><flux:table.cell class="font-semibold text-violet-600">{{ $credit->number }}</flux:table.cell><flux:table.cell>{{ $credit->invoice?->number ?? '—' }}</flux:table.cell><flux:table.cell>{{ $credit->customer?->name ?? '—' }}</flux:table.cell><flux:table.cell class="max-w-xs truncate">{{ $credit->reason }}</flux:table.cell><flux:table.cell>{{ $credit->credit_date->format('d/m/Y') }}</flux:table.cell><flux:table.cell align="end" class="font-semibold"><x-money :amount="$credit->total_amount" /></flux:table.cell></flux:table.row>
                        @empty <flux:table.row><flux:table.cell colspan="6" class="py-12! text-center"><x-table-empty icon="arrow-uturn-left" title="Aucun avoir comptabilisé" description="Les corrections de factures apparaîtront ici." /></flux:table.cell></flux:table.row>@endforelse
                    </flux:table.rows></flux:table>
                @elseif($activeTab === 'payments')
                    <flux:table><flux:table.columns><flux:table.column><x-table-heading icon="banknotes">Paiement</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="document-text">Facture</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="credit-card">Moyen</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="calendar-days">Date</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="calculator">Montant</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                        @forelse($payments as $payment)<flux:table.row><flux:table.cell class="font-semibold text-violet-600">{{ $payment->number }}</flux:table.cell><flux:table.cell>{{ $payment->invoice->number }}</flux:table.cell><flux:table.cell>{{ $payment->invoice->customer?->name ?? '—' }}</flux:table.cell><flux:table.cell><flux:badge color="blue">{{ ucfirst($payment->method) }}</flux:badge></flux:table.cell><flux:table.cell>{{ $payment->payment_date->format('d/m/Y') }}</flux:table.cell><flux:table.cell align="end" class="font-semibold"><x-money :amount="$payment->amount" /></flux:table.cell></flux:table.row>
                        @empty <flux:table.row><flux:table.cell colspan="6" class="py-12! text-center"><x-table-empty icon="banknotes" title="Aucun paiement enregistré" description="Enregistrez un règlement client pour alimenter cette liste." /></flux:table.cell></flux:table.row>@endforelse
                    </flux:table.rows></flux:table>
                @elseif($activeTab === 'expenses')
                    <flux:table><flux:table.columns><flux:table.column><x-table-heading icon="receipt-percent">Pièce</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="building-storefront">Fournisseur</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="bars-3-bottom-left">Description</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="book-open">Compte</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="calendar-days">Date</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">Total</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                        @forelse($expenses as $expense)<flux:table.row><flux:table.cell class="font-semibold text-rose-600">{{ $expense->number }}</flux:table.cell><flux:table.cell>{{ $expense->supplier ?: '—' }}</flux:table.cell><flux:table.cell>{{ $expense->description }}</flux:table.cell><flux:table.cell><flux:badge color="zinc">{{ $expense->expense_account }}</flux:badge></flux:table.cell><flux:table.cell>{{ $expense->expense_date->format('d/m/Y') }}</flux:table.cell><flux:table.cell align="end" class="font-semibold"><x-money :amount="$expense->total_amount" /></flux:table.cell></flux:table.row>
                        @empty <flux:table.row><flux:table.cell colspan="6" class="py-12! text-center"><x-table-empty icon="receipt-percent" title="Aucune dépense comptabilisée" description="Les charges et leurs justificatifs apparaîtront ici." /></flux:table.cell></flux:table.row>@endforelse
                    </flux:table.rows></flux:table>
                @elseif($activeTab === 'customers')
                    <flux:table><flux:table.columns><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="phone">Téléphone</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="envelope">Email</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="shopping-bag">Ventes comptoir</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="signal">État</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                        @forelse($customers as $customer)<flux:table.row><flux:table.cell class="font-semibold">{{ $customer->name }}</flux:table.cell><flux:table.cell>{{ $customer->phone ?: '—' }}</flux:table.cell><flux:table.cell>{{ $customer->email ?: '—' }}</flux:table.cell><flux:table.cell align="end">{{ $customer->sales_count }}</flux:table.cell><flux:table.cell><flux:badge :color="$customer->is_active ? 'green' : 'zinc'">{{ $customer->is_active ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell></flux:table.row>
                        @empty <flux:table.row><flux:table.cell colspan="5" class="py-12! text-center"><x-table-empty icon="users" title="Aucun client" description="Ajoutez un client depuis l’espace Clients & fidélité." /></flux:table.cell></flux:table.row>@endforelse
                    </flux:table.rows></flux:table>
                @elseif($activeTab === 'products')
                    <flux:table><flux:table.columns><flux:table.column><x-table-heading icon="cube">Produit</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="hashtag">SKU</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="tag">Type</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">Prix de vente</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="calculator">Coût</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="book-open">Compte produit</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                        @forelse($products as $product)<flux:table.row><flux:table.cell class="font-semibold">{{ $product->name }}</flux:table.cell><flux:table.cell>{{ $product->sku ?: '—' }}</flux:table.cell><flux:table.cell><flux:badge color="blue">{{ ucfirst($product->type) }}</flux:badge></flux:table.cell><flux:table.cell align="end"><x-money :amount="$product->price" /></flux:table.cell><flux:table.cell align="end"><x-money :amount="$product->cost" /></flux:table.cell><flux:table.cell><flux:badge color="violet">{{ $product->type === 'service' ? '706' : '701' }}</flux:badge></flux:table.cell></flux:table.row>
                        @empty <flux:table.row><flux:table.cell colspan="6" class="py-12! text-center"><x-table-empty icon="cube" title="Aucun produit actif" description="Les articles actifs de l’inventaire apparaîtront ici." /></flux:table.cell></flux:table.row>@endforelse
                    </flux:table.rows></flux:table>
                @elseif($activeTab === 'journal')
                    @php
                        $pageDebit = (float) $records->sum('debit');
                        $pageCredit = (float) $records->sum('credit');
                    @endphp
                    <div class="border-b border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div class="mb-4 flex items-center justify-between gap-4"><div><flux:heading size="sm">Période et plan comptable</flux:heading><flux:text size="sm">Filtrez les écritures sans modifier le journal d’origine.</flux:text></div><flux:badge color="violet" icon="shield-check">Journal sécurisé</flux:badge></div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><flux:input type="date" wire:model.live="filterStartDate" label="Date de début" /><flux:input type="date" wire:model.live="filterEndDate" label="Date de fin" /><flux:input wire:model.live.debounce.300ms="filterAccount" label="Compte SYSCOHADA" icon="magnifying-glass" placeholder="Ex. 411" /><flux:select wire:model.live="perPage" label="Lignes par page"><flux:select.option value="15">15 lignes</flux:select.option><flux:select.option value="30">30 lignes</flux:select.option><flux:select.option value="50">50 lignes</flux:select.option></flux:select></div>
                    </div>
                    <flux:table>
                        <flux:table.columns><flux:table.column><x-table-heading icon="calendar-days">Date</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="book-open">Journal</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="hashtag">Référence</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="building-library">Compte</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="bars-3-bottom-left">Libellé</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="arrow-down-left">Débit</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="arrow-up-right">Crédit</x-table-heading></flux:table.column></flux:table.columns>
                        <flux:table.rows>
                            @forelse($records as $record)
                                <flux:table.row>
                                    <flux:table.cell><div class="flex items-center gap-2 whitespace-nowrap"><span class="grid size-8 place-items-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon.calendar-days class="size-4" /></span><span class="font-medium">{{ $record->record_date->format('d/m/Y') }}</span></div></flux:table.cell>
                                    <flux:table.cell><flux:badge :color="match($record->journal_code) { 'VE' => 'green', 'AC' => 'amber', 'TR' => 'blue', 'AV' => 'red', default => 'violet' }" icon="book-open">{{ $record->journal_code }}</flux:badge></flux:table.cell>
                                    <flux:table.cell><span class="rounded-lg bg-zinc-100 px-2 py-1 font-mono text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $record->reference }}</span></flux:table.cell>
                                    <flux:table.cell><span class="font-semibold tabular-nums text-zinc-950 dark:text-white">{{ $record->syscohada_code }}</span></flux:table.cell>
                                    <flux:table.cell><div class="min-w-48 max-w-md"><div class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $record->description }}</div><div class="mt-1 text-xs text-zinc-500">Saisi par {{ $record->user?->name ?? 'Système' }}</div></div></flux:table.cell>
                                    <flux:table.cell align="end"><span class="font-semibold tabular-nums {{ $record->debit > 0 ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-400' }}">@if($record->debit > 0)<x-money :amount="$record->debit" />@else—@endif</span></flux:table.cell>
                                    <flux:table.cell align="end"><span class="font-semibold tabular-nums {{ $record->credit > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-400' }}">@if($record->credit > 0)<x-money :amount="$record->credit" />@else—@endif</span></flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row><flux:table.cell colspan="7" class="py-14! text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 dark:bg-zinc-800"><flux:icon.book-open class="size-6 text-zinc-400" /></span><flux:heading size="lg" class="mt-4">Aucune écriture</flux:heading><flux:text class="mt-1">Aucune écriture ne correspond à cette période et ce compte.</flux:text></flux:table.cell></flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                    @if($records->isNotEmpty())
                        <div class="grid gap-3 border-t border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-900/50 sm:grid-cols-3">
                            <div><flux:text size="sm">Mouvements affichés</flux:text><div class="mt-1 font-semibold">{{ $records->count() }} écriture{{ $records->count() > 1 ? 's' : '' }}</div></div>
                            <div class="sm:text-right"><flux:text size="sm">Total débit de la page</flux:text><div class="mt-1 font-semibold tabular-nums text-blue-700 dark:text-blue-300"><x-money :amount="$pageDebit" /></div></div>
                            <div class="sm:text-right"><flux:text size="sm">Total crédit de la page</flux:text><div class="mt-1 font-semibold tabular-nums text-emerald-700 dark:text-emerald-300"><x-money :amount="$pageCredit" /></div></div>
                        </div>
                    @endif
                    <div class="flex flex-col gap-3 border-t border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                        <flux:text size="sm">Affichage de <strong class="text-zinc-900 dark:text-white">{{ $records->firstItem() ?? 0 }}</strong> à <strong class="text-zinc-900 dark:text-white">{{ $records->lastItem() ?? 0 }}</strong> sur <strong class="text-zinc-900 dark:text-white">{{ $records->total() }}</strong> écritures</flux:text>
                        @if($records->hasPages())<div>{{ $records->onEachSide(1)->links() }}</div>@else<flux:badge color="zinc">Page unique</flux:badge>@endif
                    </div>
                @endif
            </div>
        </flux:card>
    @endif

    <flux:modal wire:model="showExpenseModal" variant="flyout" position="right" class="w-full md:w-[40rem]">
        <flux:heading size="lg">Nouvelle dépense</flux:heading><flux:text class="mt-1">La charge, la TVA récupérable et la contrepartie sont comptabilisées automatiquement.</flux:text>
        <form wire:submit="createExpense" class="mt-6 space-y-5"><div class="grid gap-4 sm:grid-cols-2"><flux:input type="date" label="Date" wire:model="expenseDate" /><flux:input label="Fournisseur" wire:model="expenseSupplier" /><flux:input class="sm:col-span-2" label="Description" wire:model="expenseDescription" /><flux:select label="Compte de charge OHADA" wire:model="expenseAccount"><flux:select.option value="601">601 · Achats de marchandises</flux:select.option><flux:select.option value="605">605 · Autres achats</flux:select.option><flux:select.option value="622">622 · Locations</flux:select.option><flux:select.option value="628">628 · Télécommunications</flux:select.option><flux:select.option value="658">658 · Charges diverses</flux:select.option></flux:select><flux:select label="Paiement" wire:model.live="expensePaymentMethod"><flux:select.option value="cash">Espèces</flux:select.option><flux:select.option value="bank">Banque</flux:select.option><flux:select.option value="mobile">Mobile money</flux:select.option><flux:select.option value="supplier">À payer au fournisseur</flux:select.option></flux:select><flux:input type="number" step="0.01" label="Montant HT" wire:model="expenseSubtotal" suffix="{{ $currency }}" /><flux:input type="number" step="0.01" label="TVA récupérable" wire:model="expenseTaxAmount" suffix="{{ $currency }}" /><flux:input label="Compte de contrepartie" description="5711 caisse · 5211 banque · 4011 fournisseur" wire:model="expenseCreditAccount" /><flux:input label="Référence justificatif" wire:model="expenseReceipt" /></div><flux:textarea label="Notes internes" wire:model="expenseNotes" /><div class="flex justify-end gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800"><flux:button type="button" variant="ghost" wire:click="$set('showExpenseModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet" icon="check">Comptabiliser</flux:button></div></form>
    </flux:modal>

    <flux:modal wire:model="showPaymentModal" class="md:w-[36rem]">
        <flux:heading size="lg">Enregistrer un paiement client</flux:heading><form wire:submit="recordPayment" class="mt-6 space-y-4"><flux:select label="Facture à régler" wire:model.live="paymentInvoiceId"><flux:select.option value="">Sélectionner…</flux:select.option>@foreach($openInvoices as $invoice)<flux:select.option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $invoice->customer?->name }} · {{ number_format($invoice->balance_due, 2, ',', ' ') }} {{ $currency }}</flux:select.option>@endforeach</flux:select><div class="grid gap-4 sm:grid-cols-2"><flux:input type="date" label="Date" wire:model="paymentDate" /><flux:input type="number" step="0.01" label="Montant" wire:model="paymentAmount" suffix="{{ $currency }}" /><flux:select label="Moyen" wire:model.live="paymentMethod"><flux:select.option value="cash">Espèces</flux:select.option><flux:select.option value="bank">Banque</flux:select.option><flux:select.option value="mobile">Mobile money</flux:select.option></flux:select><flux:input label="Compte de trésorerie" wire:model="paymentTreasuryAccount" description="5711 caisse · 5211 banque" /></div><flux:input label="Référence externe" wire:model="paymentReference" /><div class="flex justify-end gap-2 pt-3"><flux:button type="button" variant="ghost" wire:click="$set('showPaymentModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet">Enregistrer le paiement</flux:button></div></form>
    </flux:modal>

    <flux:modal wire:model="showCreditModal" class="md:w-[36rem]">
        <flux:heading size="lg">Créer un avoir</flux:heading><flux:text class="mt-1">L’avoir réduit automatiquement la créance, le produit et la TVA collectée.</flux:text><form wire:submit="createCreditNote" class="mt-6 space-y-4"><flux:select label="Facture d’origine" wire:model.live="creditInvoiceId"><flux:select.option value="">Sélectionner…</flux:select.option>@foreach($openInvoices as $invoice)<flux:select.option value="{{ $invoice->id }}">{{ $invoice->number }} · solde {{ number_format($invoice->balance_due, 2, ',', ' ') }}</flux:select.option>@endforeach</flux:select><div class="grid gap-4 sm:grid-cols-2"><flux:input type="date" label="Date" wire:model="creditDate" /><flux:input type="number" step="0.01" label="Montant HT" wire:model="creditSubtotal" suffix="{{ $currency }}" /><flux:input type="number" step="0.01" label="TVA à annuler" wire:model="creditTaxAmount" suffix="{{ $currency }}" /></div><flux:textarea label="Motif obligatoire" wire:model="creditReason" /><div class="flex justify-end gap-2 pt-3"><flux:button type="button" variant="ghost" wire:click="$set('showCreditModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet">Valider l’avoir</flux:button></div></form>
    </flux:modal>

    <flux:modal wire:model="showEntryModal" class="md:w-[34rem]">
        <flux:heading size="lg">Écriture diverse</flux:heading><flux:callout class="mt-4" icon="information-circle">Utilisez OD pour les ajustements justifiés. La saisie crée toujours un débit et un crédit de même montant.</flux:callout><form wire:submit="createEntry" class="mt-5 space-y-4"><flux:input type="date" label="Date" wire:model="recordDate" /><div class="grid gap-4 sm:grid-cols-2"><flux:input label="Compte débité" wire:model="debitCode" /><flux:input label="Compte crédité" wire:model="creditCode" /></div><flux:input type="number" step="0.01" label="Montant" wire:model="amount" suffix="{{ $currency }}" /><flux:textarea label="Libellé et justification" wire:model="description" /><div class="flex justify-end gap-2"><flux:button type="button" variant="ghost" wire:click="$set('showEntryModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet">Enregistrer</flux:button></div></form>
    </flux:modal>
</div>
