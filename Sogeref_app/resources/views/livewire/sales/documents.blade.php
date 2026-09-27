<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$isQuote ? 'Devis' : 'Factures'" eyebrow="Ventes"
                      :subtitle="$isQuote ? 'Propositions commerciales : validez-les pour ouvrir un dossier de commande.' : 'Factures clients, encaissements et relances.'">
        <x-slot:actions>
            @if (auth()->user()->canManage($isQuote ? 'quotes' : 'invoices'))
                <x-ui.button variant="accent" icon="plus" :href="route($isQuote ? 'quotes.create' : 'invoices.create')">{{ $isQuote ? 'Nouveau devis' : 'Nouvelle facture' }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. INDICATEURS ═══════════════════════════════════════════════════ --}}
    <div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ($stats as [$label, $value, $icon, $tone])
            <x-ui.stat :label="$label" :value="$value" :icon="$icon" :tone="$tone" />
        @endforeach
    </div>

    {{-- ═══ 3. LISTE ═════════════════════════════════════════════════════════ --}}
    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex gap-1 overflow-x-auto scrollbar-none">
                <button type="button" wire:click="$set('status', '')" @class(['btn btn-sm', 'btn-soft' => $status === '', 'btn-ghost' => $status !== ''])>Tous</button>
                @foreach ($statuses as $s)
                    <button type="button" wire:click="$set('status', '{{ $s }}')" @class(['btn btn-sm', 'btn-soft' => $status === $s, 'btn-ghost' => $status !== $s])>{{ erp_status('document', $s)[0] }}</button>
                @endforeach
                @unless ($isQuote)
                    <button type="button" wire:click="$set('status', 'overdue')" @class(['btn btn-sm', 'btn-soft' => $status === 'overdue', 'btn-ghost' => $status !== 'overdue'])>En retard</button>
                @endunless
            </div>
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="N° ou client…" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>N°</th><th>Client</th><th>Date</th><th>{{ $isQuote ? 'Validité' : 'Échéance' }}</th><th>Statut</th><th class="num">Total TTC</th>@unless ($isQuote)<th class="num">Reste dû</th>@endunless<th></th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php $late = ! $isQuote && $document->due_date?->lt(today()) && ! in_array($document->status->value, ['paid', 'cancelled', 'draft']); @endphp
                        <tr wire:key="d-{{ $document->id }}">
                            <td><a href="{{ route('documents.show', $document) }}" wire:navigate class="link">{{ $document->number }}</a></td>
                            <td><div class="flex items-center gap-2.5"><x-ui.avatar :name="$document->contact->display_name" size="xs" /><span class="max-w-[220px] truncate">{{ $document->contact->display_name }}</span></div></td>
                            <td class="whitespace-nowrap text-fg-muted">{{ $document->issue_date->format('d/m/Y') }}</td>
                            <td @class(['whitespace-nowrap', 'font-semibold text-flame-600' => $late, 'text-fg-muted' => ! $late])>{{ ($isQuote ? $document->valid_until : $document->due_date)?->format('d/m/Y') ?? '—' }}</td>
                            <td>@if ($late)<x-ui.badge tone="danger" dot>En retard</x-ui.badge>@else<x-ui.status map="document" :value="$document->status" />@endif</td>
                            <td class="num font-semibold">{{ money($document->total, $document->currency_code) }}</td>
                            @unless ($isQuote)<td class="num {{ $document->balance_due > 0 ? 'text-fg' : 'text-fg-subtle' }}">{{ money($document->balance_due, $document->currency_code) }}</td>@endunless
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('pdf.document', $document) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="PDF"><x-ui.icon name="printer" class="size-4" /></a>
                                <x-ui.button variant="ghost" size="sm" square icon="chevron-right" :href="route('documents.show', $document)" aria-label="Ouvrir" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty :icon="$isQuote ? 'document-text' : 'document-currency-dollar'" :title="$isQuote ? 'Aucun devis' : 'Aucune facture'" text="Aucun document ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($documents->hasPages())<div class="border-t border-line px-4 py-3">{{ $documents->links('components.ui.pagination') }}</div>@endif
    </div>
</div>
