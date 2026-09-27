@php
    $companyName = \App\Models\Setting::get('company_name', 'Yeboox App');
    $companyAddress = \App\Models\Setting::get('company_address', '');
    $companyPhone = \App\Models\Setting::get('company_phone', '');
    $companyEmail = \App\Models\Setting::get('company_email', '');
    $companyRccm = \App\Models\Setting::get('company_rccm', '');
    $companyNif = \App\Models\Setting::get('company_nif', '');
    $companyTva = \App\Models\Setting::get('company_tva', '');
    $companyFooter = \App\Models\Setting::get('company_footer', 'Merci pour votre confiance.');
    $logoPath = \App\Models\Setting::get('company_logo');
    $logoUrl = $logoPath ? asset('storage/'.ltrim(str_replace('public/', '', $logoPath), '/')) : null;
    $activeFilters = collect([$search, $filterStatus, $filterStartDate, $filterEndDate])->filter()->count();
    $summaryCards = [
        ['Facturé validé', $totalInvoiced, $currency, 'document-check', 'violet'],
        ['Encaissé', $totalPaid, 'Paiements cumulés', 'banknotes', 'emerald'],
        ['À recevoir', $totalUnpaid, 'Créances ouvertes', 'clock', 'amber'],
        ['En retard', $totalOverdue, 'Échéances dépassées', 'exclamation-triangle', 'rose'],
        ['Brouillons', $totalDraft, 'Documents à finaliser', 'pencil-square', 'zinc'],
    ];
@endphp

<div class="space-y-6">
    <div x-data="{ show: false, message: '' }" x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3200)" x-show="show" x-transition class="fixed right-5 top-5 z-[100]" style="display:none"><flux:callout variant="success" icon="check-circle" x-text="message" /></div>

    {{-- En-tête partagé : cycle de facturation et création des documents clients. --}}
    <x-page-hero
        title="Facturation clients"
        eyebrow="Cycle client"
        description="Créez les factures, suivez les échéances et enregistrez chaque règlement client."
        icon="document-text"
        badge-icon="document-check"
        illustration="invoices"
    >
        <x-slot:actions>
            <flux:button href="{{ route('accounting') }}" wire:navigate variant="filled" icon="scale" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!">Journal comptable</flux:button>
            <flux:button wire:click="openCreateModal" variant="primary" color="zinc" icon="document-plus" class="bg-white! text-indigo-900! hover:bg-violet-50!">Nouvelle facture</flux:button>
        </x-slot:actions>
    </x-page-hero>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-5" aria-label="Indicateurs de facturation">
        @foreach($summaryCards as [$label, $value, $description, $icon, $color])
            <flux:card class="p-4! last:col-span-2 sm:p-5! lg:last:col-span-1">
                <div class="flex items-center justify-between gap-3"><flux:text size="sm">{{ $label }}</flux:text><span @class(['grid size-9 shrink-0 place-items-center rounded-xl', 'bg-violet-50 text-violet-600 dark:bg-violet-500/10' => $color === 'violet', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' => $color === 'emerald', 'bg-amber-50 text-amber-600 dark:bg-amber-500/10' => $color === 'amber', 'bg-rose-50 text-rose-600 dark:bg-rose-500/10' => $color === 'rose', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800' => $color === 'zinc'])><flux:icon :name="$icon" class="size-4" /></span></div>
                <div class="mt-4 truncate text-xl font-semibold text-zinc-950 dark:text-white sm:text-2xl">@if($label === 'Brouillons'){{ number_format($value) }}@else<x-money :amount="$value" />@endif</div><flux:text size="sm" class="mt-1">{{ $description }}</flux:text>
            </flux:card>
        @endforeach
    </section>

    <flux:card class="p-4! sm:p-5!">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10"><flux:icon.funnel class="size-4" /></span><div><flux:heading size="sm">Recherche et filtres</flux:heading><flux:text size="sm">Affinez la liste par client, statut ou période.</flux:text></div></div>
            <div class="flex items-center gap-2"><flux:badge :color="$activeFilters ? 'violet' : 'zinc'">{{ $activeFilters }} actif{{ $activeFilters > 1 ? 's' : '' }}</flux:badge>@if($activeFilters)<flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">Effacer</flux:button>@endif</div>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(15rem,1fr)_13rem_11rem_11rem_9rem]">
            <flux:input wire:model.live.debounce.300ms="search" label="Recherche" icon="magnifying-glass" placeholder="N° facture ou client…" clearable />
            <flux:select wire:model.live="filterStatus" label="Statut"><flux:select.option value="">Tous les statuts</flux:select.option><flux:select.option value="draft">Brouillons</flux:select.option><flux:select.option value="unpaid">À payer</flux:select.option><flux:select.option value="paid">Payées</flux:select.option><flux:select.option value="overdue">En retard</flux:select.option></flux:select>
            <flux:input type="date" wire:model.live="filterStartDate" label="Du" />
            <flux:input type="date" wire:model.live="filterEndDate" label="Au" />
            <flux:select wire:model.live="perPage" label="Affichage"><flux:select.option value="15">15 / page</flux:select.option><flux:select.option value="30">30 / page</flux:select.option><flux:select.option value="50">50 / page</flux:select.option></flux:select>
        </div>
    </flux:card>

    <section class="grid gap-4 md:hidden" aria-label="Liste mobile des factures">
        @forelse($invoices as $invoice)
            @php
                $balance = $invoice->balance_due;
                $isOverdue = $invoice->status === 'unpaid' && $invoice->due_date?->isPast();
                $isPartial = $invoice->status === 'unpaid' && (float) $invoice->paid_amount > 0;
            @endphp
            <flux:card class="p-0!">
                <div class="flex items-start gap-4 p-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10"><flux:icon.document-text class="size-5" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2"><button wire:click="openViewModal({{ $invoice->id }})" class="min-w-0 text-left"><span class="block truncate font-semibold text-violet-600">{{ $invoice->number }}</span><span class="block truncate text-sm text-zinc-500">{{ $invoice->customer?->name ?? 'Client comptant' }}</span></button>@include('livewire.partials.invoice-actions', ['invoice' => $invoice, 'balance' => $balance])</div>
                        <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/40"><div><div class="text-xs text-zinc-500">Émission</div><div class="mt-1 text-sm font-medium">{{ $invoice->invoice_date->format('d/m/Y') }}</div></div><div class="text-right"><div class="text-xs text-zinc-500">Total</div><div class="mt-1 text-sm font-semibold"><x-money :amount="$invoice->total_amount" /></div></div></div>
                        <div class="mt-3 flex items-center justify-between">@if($invoice->status === 'paid')<flux:badge color="green" icon="check">Payée</flux:badge>@elseif($isOverdue)<flux:badge color="red" icon="exclamation-triangle">En retard</flux:badge>@elseif($isPartial)<flux:badge color="blue" icon="banknotes">Partielle</flux:badge>@elseif($invoice->status === 'unpaid')<flux:badge color="amber" icon="clock">À payer</flux:badge>@else<flux:badge color="zinc" icon="pencil-square">Brouillon</flux:badge>@endif @if($invoice->status !== 'draft')<span class="text-xs font-medium {{ $balance > 0 ? 'text-amber-600' : 'text-emerald-600' }}">Solde {{ \App\Support\DisplayCurrency::format($balance) }}</span>@endif</div>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="py-14! text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 dark:bg-zinc-800"><flux:icon.document-text class="size-6 text-zinc-400" /></span><flux:heading size="lg" class="mt-4">Aucune facture trouvée</flux:heading><flux:text class="mt-1">Modifiez les filtres ou créez votre première facture.</flux:text><flux:button wire:click="openCreateModal" variant="primary" color="violet" icon="document-plus" class="mt-5">Nouvelle facture</flux:button></flux:card>
        @endforelse
    </section>

    <flux:card class="hidden overflow-hidden p-0! md:block">
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800"><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10"><flux:icon.document-text class="size-4" /></span><div><flux:heading size="lg">Registre des factures</flux:heading><flux:text size="sm">{{ $invoices->total() }} document{{ $invoices->total() > 1 ? 's' : '' }} trouvé{{ $invoices->total() > 1 ? 's' : '' }}</flux:text></div></div><flux:badge color="violet" icon="check-badge">Suivi comptable</flux:badge></div>
        <div class="overflow-x-auto"><flux:table class="[&_th]:px-5! [&_td]:px-5! [&_th]:py-4! [&_td]:py-4!">
            <flux:table.columns><flux:table.column sortable wire:click="sortBy('number')" :sorted="$sortField === 'number'" :direction="$sortDirection"><x-table-heading icon="document-text">Facture</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column sortable wire:click="sortBy('invoice_date')" :sorted="$sortField === 'invoice_date'" :direction="$sortDirection"><x-table-heading icon="calendar-days">Émission</x-table-heading></flux:table.column><flux:table.column sortable wire:click="sortBy('due_date')" :sorted="$sortField === 'due_date'" :direction="$sortDirection"><x-table-heading icon="clock">Échéance</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="signal">Statut</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">Total / Solde</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="ellipsis-horizontal">Actions</x-table-heading></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse($invoices as $invoice)
                    @php
                        $balance = $invoice->balance_due;
                        $isOverdue = $invoice->status === 'unpaid' && $invoice->due_date?->isPast();
                        $isPartial = $invoice->status === 'unpaid' && (float) $invoice->paid_amount > 0;
                    @endphp
                    <flux:table.row>
                        <flux:table.cell><button type="button" wire:click="openViewModal({{ $invoice->id }})" class="flex items-center gap-3 text-left"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10"><flux:icon.document-text class="size-4" /></span><span><span class="block font-semibold text-violet-600 hover:underline">{{ $invoice->number }}</span><span class="text-xs text-zinc-500">{{ $invoice->posted_at ? 'Journal VE' : 'Non comptabilisée' }}</span></span></button></flux:table.cell>
                        <flux:table.cell><div class="font-medium text-zinc-950 dark:text-white">{{ $invoice->customer?->name ?? 'Client comptant' }}</div><div class="mt-1 flex items-center gap-1 text-xs text-zinc-500"><flux:icon.phone class="size-3" />{{ $invoice->customer?->phone ?: 'Aucun téléphone' }}</div></flux:table.cell>
                        <flux:table.cell><div class="flex items-center gap-2"><flux:icon.calendar-days class="size-4 text-zinc-400" /><span>{{ $invoice->invoice_date->format('d/m/Y') }}</span></div></flux:table.cell>
                        <flux:table.cell><div class="flex items-start gap-2"><flux:icon.clock class="mt-0.5 size-4 {{ $isOverdue ? 'text-rose-500' : 'text-zinc-400' }}" /><div><span class="{{ $isOverdue ? 'font-semibold text-rose-600' : '' }}">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</span>@if($isOverdue)<div class="mt-1 text-xs text-rose-500">Retard de {{ $invoice->due_date->diffInDays(today()) }} j</div>@endif</div></div></flux:table.cell>
                        <flux:table.cell>@if($invoice->status === 'paid')<flux:badge color="green" icon="check">Payée</flux:badge>@elseif($isOverdue)<flux:badge color="red" icon="exclamation-triangle">En retard</flux:badge>@elseif($isPartial)<flux:badge color="blue" icon="banknotes">Partielle</flux:badge>@elseif($invoice->status === 'unpaid')<flux:badge color="amber" icon="clock">À payer</flux:badge>@else<flux:badge color="zinc" icon="pencil-square">Brouillon</flux:badge>@endif</flux:table.cell>
                        <flux:table.cell align="end"><div class="font-semibold tabular-nums text-zinc-950 dark:text-white"><x-money :amount="$invoice->total_amount" /></div>@if($invoice->status !== 'draft')<div class="mt-1 text-xs {{ $balance > 0 ? 'text-amber-600' : 'text-emerald-600' }}">Solde {{ \App\Support\DisplayCurrency::format($balance) }}</div>@endif</flux:table.cell>
                        <flux:table.cell align="end">@include('livewire.partials.invoice-actions', ['invoice' => $invoice, 'balance' => $balance])</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7" class="py-14! text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-zinc-100 dark:bg-zinc-800"><flux:icon.document-text class="size-6 text-zinc-400" /></span><flux:heading size="lg" class="mt-4">Aucune facture trouvée</flux:heading><flux:text class="mt-1">Modifiez les filtres ou créez votre première facture.</flux:text><flux:button wire:click="openCreateModal" variant="primary" color="violet" icon="document-plus" class="mt-5">Nouvelle facture</flux:button></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table></div>
        @if($invoices->hasPages())<div class="flex items-center justify-between border-t border-zinc-200 px-5 py-4 dark:border-zinc-800"><flux:text size="sm">Page {{ $invoices->currentPage() }} sur {{ $invoices->lastPage() }}</flux:text><div>{{ $invoices->links() }}</div></div>@endif
    </flux:card>

    <flux:modal wire:model="showFormModal" variant="flyout" position="right" class="w-full md:w-[58rem]">
        <div class="flex items-start gap-4 border-b border-zinc-200 pb-5 pr-10 dark:border-zinc-800"><span class="flex size-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"><flux:icon.document-plus class="size-5" /></span><div><flux:heading size="lg">{{ $editingInvoiceId ? 'Modifier le brouillon' : 'Nouvelle facture' }}</flux:heading><flux:text class="mt-1">Préparez les lignes, puis enregistrez en brouillon ou validez directement.</flux:text></div></div>
        <form wire:submit="saveInvoice" class="mt-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"><flux:select label="Client" wire:model="customer_id"><flux:select.option value="">Client comptant</flux:select.option>@foreach($customers as $customer)<flux:select.option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->phone }}</flux:select.option>@endforeach</flux:select><flux:input type="date" label="Date d’émission" wire:model="invoice_date" /><flux:input type="date" label="Échéance" wire:model="due_date" /><flux:select label="Traitement" wire:model="status"><flux:select.option value="draft">Enregistrer en brouillon</flux:select.option><flux:select.option value="unpaid">Valider et comptabiliser</flux:select.option></flux:select></div>
            <flux:callout icon="information-circle">Un client identifié est obligatoire pour valider une facture à crédit. Un brouillon reste modifiable et supprimable.</flux:callout>
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800"><div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-800"><div><flux:heading size="sm">Lignes de facturation</flux:heading><flux:text size="sm">Sélectionnez un produit ou saisissez une prestation libre.</flux:text></div><flux:button type="button" wire:click="addItem" icon="plus" size="sm">Ajouter une ligne</flux:button></div><div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($items as $index => $item)
                    <div wire:key="invoice-line-{{ $index }}" class="grid gap-3 p-4 md:grid-cols-[1.2fr_1.4fr_.55fr_.8fr_auto] md:items-end"><flux:select label="Produit" wire:model="items.{{ $index }}.product_id" wire:change="selectProductForItem({{ $index }}, $event.target.value)"><flux:select.option value="">Ligne libre</flux:select.option>@foreach($products as $product)<flux:select.option value="{{ $product->id }}">{{ $product->name }} · {{ \App\Support\DisplayCurrency::format($product->grossUnitPrice()) }} TTC</flux:select.option>@endforeach</flux:select><flux:input label="Description" wire:model="items.{{ $index }}.description" /><flux:input type="number" min="0.01" step="0.01" label="Qté" wire:model.live.debounce.250ms="items.{{ $index }}.quantity" /><flux:input type="number" min="0" step="0.01" label="Prix unitaire HT" wire:model.live.debounce.250ms="items.{{ $index }}.unit_price" suffix="{{ \App\Support\DisplayCurrency::baseCode() }}" /><flux:button type="button" wire:click="removeItem({{ $index }})" icon="trash" variant="ghost" aria-label="Supprimer la ligne" /></div>
                @endforeach
            </div></div>
            @error('items.*.description')<flux:error>{{ $message }}</flux:error>@enderror
            <div class="grid gap-5 lg:grid-cols-[1fr_22rem]"><flux:textarea label="Notes et conditions" wire:model="notes" rows="5" placeholder="Conditions de règlement, référence de commande…" /><div class="space-y-4 rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-800/40"><div class="grid grid-cols-2 gap-3"><flux:input type="number" min="0" max="100" step="0.01" label="TVA" wire:model.live.debounce.250ms="tax_percent" suffix="%" /><flux:input type="number" min="0" step="0.01" label="Remise" wire:model.live.debounce.250ms="discount_amount" suffix="{{ \App\Support\DisplayCurrency::baseCode() }}" /></div>@php($formSubtotal = $this->calculateSubtotal())<div class="space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700"><div class="flex justify-between"><span class="text-zinc-500">Sous-total</span><span>{{ \App\Support\DisplayCurrency::format($formSubtotal) }}</span></div><div class="flex justify-between"><span class="text-zinc-500">TVA</span><span>{{ \App\Support\DisplayCurrency::format($formSubtotal * ((float) $tax_percent / 100)) }}</span></div><div class="flex justify-between text-lg font-semibold"><span>Total</span><span class="text-violet-600">{{ \App\Support\DisplayCurrency::format($this->calculateTotal()) }}</span></div></div></div></div>
            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800"><flux:button type="button" variant="ghost" wire:click="$set('showFormModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="violet" icon="check">{{ $status === 'draft' ? 'Enregistrer le brouillon' : 'Valider la facture' }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showViewModal" class="md:w-[64rem]" x-data="{ format: 'a4' }">
        @if($selectedInvoice)
            <div class="flex flex-col gap-3 border-b border-zinc-200 pb-4 pr-10 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between"><div><flux:heading size="lg">{{ $selectedInvoice->number }}</flux:heading><flux:text class="mt-1">Aperçu, historique et impression du document.</flux:text></div><div class="flex flex-wrap gap-2"><flux:select x-model="format" class="w-36" aria-label="Format d’impression"><flux:select.option value="a4">A4</flux:select.option><flux:select.option value="a5">A5</flux:select.option><flux:select.option value="ticket">Thermique 80 mm</flux:select.option></flux:select><flux:button x-on:click="document.documentElement.dataset.invoiceFormat = format; window.print()" variant="primary" color="violet" icon="printer">Imprimer / PDF</flux:button></div></div>
            <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_17rem]">
                <article id="invoice-print-area" data-document-template="{{ $documentPreferences['template'] }}" class="invoice-document rounded-xl border bg-white p-8 text-zinc-950 shadow-sm" :class="'invoice-format-'+format" style="font-family:{{ \App\Support\DocumentPreferences::fontStack($documentPreferences['font_family']) }};font-size:{{ $documentPreferences['font_size'] }}px;font-weight:{{ $documentPreferences['font_weight'] }};border-color:{{ $documentTheme['border'] }};--document-accent:{{ $documentTheme['accent'] }};--document-dark:{{ $documentTheme['dark'] }};--document-soft:{{ $documentTheme['soft'] }}">
                    <div class="invoice-document-header flex items-start justify-between gap-6 border-b-2 pb-6" style="border-color:var(--document-accent)"><div class="flex items-start gap-4">@if($logoUrl)<img src="{{ $logoUrl }}" alt="Logo {{ $companyName }}" class="h-14 w-auto object-contain" />@endif<div><div class="text-xl font-bold uppercase" style="color:var(--document-dark)">{{ $companyName }}</div><div class="mt-1 text-xs text-zinc-600">{{ $companyAddress }}</div><div class="text-xs text-zinc-600">{{ $companyPhone }} {{ $companyEmail ? '· '.$companyEmail : '' }}</div><div class="mt-1 text-[10px] text-zinc-500">RCCM {{ $companyRccm ?: '—' }} · NIF {{ $companyNif ?: '—' }} · TVA {{ $companyTva ?: '—' }}</div></div></div><div class="text-right"><div class="invoice-document-title text-3xl font-bold tracking-tight" style="color:var(--document-accent)">FACTURE</div><div class="mt-1 font-mono font-semibold">{{ $selectedInvoice->number }}</div><div class="mt-2 text-xs text-zinc-600">Émise le {{ $selectedInvoice->invoice_date->format('d/m/Y') }}<br />Échéance {{ $selectedInvoice->due_date?->format('d/m/Y') ?? '—' }}</div><div class="mt-1 text-[9px] font-semibold uppercase" style="color:var(--document-accent)">Modèle {{ $documentTemplateMeta['label'] }}</div></div></div>
                    <div class="invoice-document-parties my-6 grid grid-cols-2 gap-5 rounded-xl p-4" style="background:var(--document-soft)"><div><div class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">Émetteur</div><div class="mt-1 text-sm font-semibold">{{ $companyName }}</div><div class="text-xs text-zinc-600">{{ $companyAddress }}</div></div><div><div class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">Facturé à</div><div class="mt-1 text-sm font-semibold">{{ $selectedInvoice->customer?->name ?? 'Client comptant' }}</div><div class="text-xs text-zinc-600">{{ $selectedInvoice->customer?->phone }}<br />{{ $selectedInvoice->customer?->email }}</div></div></div>
                    <table class="w-full text-sm"><thead><tr class="border-b border-zinc-300 text-left text-xs uppercase text-zinc-500"><th class="py-3">Désignation</th><th class="py-3 text-center">Qté</th><th class="py-3 text-right">Prix unitaire</th><th class="py-3 text-right">Total</th></tr></thead><tbody>@foreach($selectedInvoice->items as $item)<tr class="border-b border-zinc-100"><td class="py-3 font-medium">{{ $item->description }}</td><td class="py-3 text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td><td class="py-3 text-right">{{ \App\Support\DisplayCurrency::format($item->unit_price) }}</td><td class="py-3 text-right font-semibold">{{ \App\Support\DisplayCurrency::format($item->total_price) }}</td></tr>@endforeach</tbody></table>
                    <div class="mt-6 ml-auto w-full max-w-sm space-y-2 text-sm"><div class="flex justify-between"><span class="text-zinc-500">Sous-total</span><span>{{ \App\Support\DisplayCurrency::format($selectedInvoice->subtotal) }}</span></div>@if($selectedInvoice->tax_amount > 0)<div class="flex justify-between"><span class="text-zinc-500">TVA ({{ number_format($selectedInvoice->tax_percent, 2, ',', ' ') }} %)</span><span>{{ \App\Support\DisplayCurrency::format($selectedInvoice->tax_amount) }}</span></div>@endif @if($selectedInvoice->discount_amount > 0)<div class="flex justify-between text-rose-600"><span>Remise</span><span>-{{ \App\Support\DisplayCurrency::format($selectedInvoice->discount_amount) }}</span></div>@endif<div class="flex justify-between border-t-2 pt-3 text-lg font-bold" style="border-color:var(--document-dark)"><span>Total</span><span>{{ \App\Support\DisplayCurrency::format($selectedInvoice->total_amount) }}</span></div><div class="flex justify-between text-xs text-zinc-500"><span>Équivalent {{ \App\Support\DisplayCurrency::secondaryCode() }}</span><span>{{ \App\Support\DisplayCurrency::format($selectedInvoice->total_amount, \App\Support\DisplayCurrency::secondaryCode()) }}</span></div><div class="text-right text-[9px] text-zinc-400">{{ \App\Support\DisplayCurrency::rateLabel() }}</div><div class="flex justify-between text-sm"><span>Déjà payé</span><span>{{ \App\Support\DisplayCurrency::format($selectedInvoice->paid_amount) }}</span></div><div class="flex justify-between font-semibold" style="color:var(--document-accent)"><span>Solde dû</span><span>{{ \App\Support\DisplayCurrency::format($selectedInvoice->balance_due) }}</span></div></div>
                    @if($selectedInvoice->notes)<div class="mt-7 rounded-lg bg-zinc-50 p-4 text-xs text-zinc-600"><strong>Notes :</strong><div class="mt-1 whitespace-pre-line">{{ $selectedInvoice->notes }}</div></div>@endif<div class="invoice-document-footer mt-10 border-t border-zinc-200 pt-4 text-center text-[10px] text-zinc-500">{{ $companyFooter }} · Modèle {{ $documentTemplateMeta['label'] }}</div>
                </article>
                <aside class="space-y-4"><div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><flux:heading size="sm">État du document</flux:heading><div class="mt-3 flex items-center justify-between"><flux:text size="sm">Statut</flux:text><flux:badge :color="$selectedInvoice->status === 'paid' ? 'green' : ($selectedInvoice->status === 'draft' ? 'zinc' : 'amber')">{{ $selectedInvoice->status === 'paid' ? 'Payée' : ($selectedInvoice->status === 'draft' ? 'Brouillon' : 'À payer') }}</flux:badge></div><div class="mt-2 flex items-center justify-between"><flux:text size="sm">Comptabilisée</flux:text><span class="text-sm font-medium">{{ $selectedInvoice->posted_at ? $selectedInvoice->posted_at->format('d/m/Y') : 'Non' }}</span></div><div class="mt-2 flex items-center justify-between"><flux:text size="sm">Créée par</flux:text><span class="text-sm font-medium">{{ $selectedInvoice->user?->name ?? 'Système' }}</span></div></div>
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><div class="flex items-center justify-between"><flux:heading size="sm">Paiements</flux:heading>@if($selectedInvoice->balance_due > 0 && $selectedInvoice->posted_at)<flux:button wire:click="openPaymentModal({{ $selectedInvoice->id }}); $set('showViewModal', false)" size="sm" icon="plus" variant="ghost" aria-label="Ajouter un paiement" />@endif</div><div class="mt-3 space-y-2">@forelse($selectedInvoice->payments as $payment)<div class="flex items-center justify-between text-sm"><div><div class="font-medium">{{ $payment->number }}</div><div class="text-xs text-zinc-500">{{ $payment->payment_date->format('d/m/Y') }} · {{ ucfirst($payment->method) }}</div></div><div class="font-semibold text-emerald-600">{{ number_format($payment->amount, 2, ',', ' ') }}</div></div>@empty<flux:text size="sm">Aucun paiement.</flux:text>@endforelse</div></div>
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><flux:heading size="sm">Avoirs</flux:heading><div class="mt-3 space-y-2">@forelse($selectedInvoice->creditNotes as $credit)<div class="flex items-center justify-between text-sm"><div><div class="font-medium">{{ $credit->number }}</div><div class="text-xs text-zinc-500">{{ $credit->credit_date->format('d/m/Y') }}</div></div><div class="font-semibold text-rose-600">-{{ number_format($credit->total_amount, 2, ',', ' ') }}</div></div>@empty<flux:text size="sm">Aucun avoir.</flux:text>@endforelse</div></div>
                </aside>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model="showPaymentModal" class="md:w-[38rem]">
        <div class="flex items-start gap-4 pr-10"><span class="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.banknotes class="size-5" /></span><div><flux:heading size="lg">Enregistrer un paiement</flux:heading><flux:text class="mt-1">Le règlement alimente automatiquement le journal de trésorerie.</flux:text></div></div><form wire:submit="recordPayment" class="mt-6 space-y-4"><div class="grid gap-4 sm:grid-cols-2"><flux:input type="date" label="Date" wire:model="paymentDate" /><flux:input type="number" min="0.01" step="0.01" label="Montant" wire:model="paymentAmount" suffix="{{ $currency }}" /><flux:select label="Moyen de paiement" wire:model.live="paymentMethod"><flux:select.option value="cash">Espèces</flux:select.option><flux:select.option value="bank">Banque</flux:select.option><flux:select.option value="mobile">Mobile money</flux:select.option></flux:select><flux:input label="Compte OHADA" wire:model="paymentTreasuryAccount" description="5711 caisse · 5211 banque · 5218 mobile" /></div><flux:input label="Référence externe" wire:model="paymentReference" placeholder="N° transaction, bordereau…" /><div class="flex justify-end gap-2 pt-3"><flux:button type="button" wire:click="$set('showPaymentModal', false)" variant="ghost">Annuler</flux:button><flux:button type="submit" variant="primary" color="emerald" icon="check">Enregistrer le paiement</flux:button></div></form>
    </flux:modal>

    <flux:modal wire:model="showCreditModal" class="md:w-[38rem]">
        <div class="flex items-start gap-4 pr-10"><span class="flex size-11 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"><flux:icon.arrow-uturn-left class="size-5" /></span><div><flux:heading size="lg">Créer un avoir</flux:heading><flux:text class="mt-1">L’avoir réduit la créance, le produit et la TVA de la facture.</flux:text></div></div><form wire:submit="createCreditNote" class="mt-6 space-y-4"><div class="grid gap-4 sm:grid-cols-2"><flux:input type="date" label="Date" wire:model="creditDate" /><flux:input type="number" min="0.01" step="0.01" label="Montant HT" wire:model="creditSubtotal" suffix="{{ $currency }}" /><flux:input type="number" min="0" step="0.01" label="TVA à annuler" wire:model="creditTaxAmount" suffix="{{ $currency }}" /></div><flux:textarea label="Motif obligatoire" wire:model="creditReason" rows="4" /><div class="flex justify-end gap-2 pt-3"><flux:button type="button" wire:click="$set('showCreditModal', false)" variant="ghost">Annuler</flux:button><flux:button type="submit" variant="primary" color="rose" icon="check">Comptabiliser l’avoir</flux:button></div></form>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" class="md:w-[30rem]"><span class="flex size-11 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"><flux:icon.trash class="size-5" /></span><flux:heading size="lg" class="mt-4">Supprimer ce brouillon ?</flux:heading><flux:text class="mt-2">Cette action supprime le document et ses lignes. Une facture déjà validée ne peut jamais être supprimée.</flux:text><div class="mt-6 flex justify-end gap-2"><flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Annuler</flux:button><flux:button wire:click="confirmDeleteInvoice" variant="danger">Supprimer</flux:button></div></flux:modal>

    <style>
        /* Invoice variants mirror the ten A4 models selected in Configuration. */
        #invoice-print-area[data-document-template="minimal"] { border-radius: 0 !important; box-shadow: none !important; }
        #invoice-print-area[data-document-template="minimal"] .invoice-document-parties { border-radius: 0 !important; background: white !important; border-block: 1px solid var(--document-accent); }
        #invoice-print-area[data-document-template="corporate"] .invoice-document-header { border-left: 8px solid var(--document-accent); padding-left: 1rem; }
        #invoice-print-area[data-document-template="editorial"] .invoice-document-title { font-family: Georgia, serif; font-weight: 500; letter-spacing: .04em; }
        #invoice-print-area[data-document-template="geometric"] .invoice-document-header, #invoice-print-area[data-document-template="geometric"] .invoice-document-parties { border-radius: 0 1.5rem; }
        #invoice-print-area[data-document-template="ledger"] { border-radius: 0 !important; border: 3px double var(--document-accent) !important; }
        #invoice-print-area[data-document-template="ledger"] table th, #invoice-print-area[data-document-template="ledger"] table td { border: 1px solid var(--document-accent); padding-inline: .5rem; }
        #invoice-print-area[data-document-template="elegant"] .invoice-document-header { border-top: 2px solid var(--document-accent); padding-top: 1rem; }
        #invoice-print-area[data-document-template="elegant"] .invoice-document-title { font-family: Georgia, serif; font-weight: 500; }
        #invoice-print-area[data-document-template="modern"] .invoice-document-header { border-radius: .75rem; padding: 1rem; background: var(--document-soft); }
        #invoice-print-area[data-document-template="framed"] { border: 2px solid var(--document-accent) !important; border-radius: 0 !important; }
        #invoice-print-area[data-document-template="compact"] { padding: 1.25rem !important; }
        #invoice-print-area[data-document-template="compact"] .invoice-document-parties { margin-block: 1rem; padding: .75rem; }
        #invoice-print-area[data-document-template="compact"] td, #invoice-print-area[data-document-template="compact"] th { padding-block: .4rem; }
        #invoice-print-area[data-document-template="official"] .invoice-document-header { border: 1px solid var(--document-accent); border-top-width: 5px; padding: 1rem; }
        #invoice-print-area[data-document-template="heritage"] .invoice-document-header { border-width: 1px 0 4px; font-family: Georgia, serif; }
        #invoice-print-area[data-document-template="heritage"] .invoice-document-title { font-family: Georgia, serif; font-weight: 500; }
        #invoice-print-area[data-document-template="sahara"] .invoice-document-header { border: 0; border-radius: .75rem .125rem; padding: 1rem; background: var(--document-soft); }
        #invoice-print-area[data-document-template="digital"] .invoice-document-header { border: 1px solid var(--document-accent); border-bottom-width: 4px; padding: 1rem; background: var(--document-dark); color: white; }
        #invoice-print-area[data-document-template="digital"] .invoice-document-header * { color: white !important; }
        #invoice-print-area[data-document-template="digital"] .invoice-document-title { font-family: ui-monospace, monospace; }

        @media print {
            body * { visibility: hidden !important; }
            #invoice-print-area, #invoice-print-area * { visibility: visible !important; }
            #invoice-print-area { position: absolute !important; inset: 0 auto auto 0 !important; width: 100% !important; border: 0 !important; box-shadow: none !important; }
            html[data-invoice-format="a4"] { --invoice-page: A4; }
            html[data-invoice-format="a5"] { --invoice-page: A5; }
            html[data-invoice-format="ticket"] #invoice-print-area { width: 80mm !important; padding: 4mm !important; font-size: 11px !important; }
        }
        @page { size: var(--invoice-page, A4); margin: 10mm; }
    </style>
</div>
