<div class="space-y-6">
    <x-page-header title="Facturation" subtitle="Factures liées au numéro de commande (référence métier principale), factures boutique, acomptes et avoirs.">
        <x-slot:actions>@can('invoices.export')<flux:button size="sm" icon="arrow-down-tray" wire:click="export">Excel</flux:button>@endcan</x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 md:grid-cols-4">
        <x-kpi label="Facturé" :value="\App\Services\Support\Money::format($totals['invoiced'])" icon="document-text" />
        <x-kpi label="Encaissé" :value="\App\Services\Support\Money::format($totals['paid'])" icon="banknotes" color="emerald" />
        <x-kpi label="Reste à encaisser" :value="\App\Services\Support\Money::format($totals['invoiced'] - $totals['paid'])" icon="clock" color="amber" />
        <x-kpi label="Échu impayé" :value="\App\Services\Support\Money::format($totals['overdue'])" icon="exclamation-triangle" color="red" />
    </div>

    <div class="panel p-4 space-y-4">
        <div class="grid gap-3 md:grid-cols-6">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N° facture, n° commande, client…" class="md:col-span-2" />
            <flux:select wire:model.live="status"><flux:select.option value="">Tous statuts</flux:select.option>@foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            <flux:input type="date" wire:model.live="from" />
            <flux:input type="date" wire:model.live="to" />
            <flux:switch wire:model.live="unpaidOnly" label="Impayées" />
        </div>
        <flux:table :paginate="$invoices">
            <flux:table.columns>
                <flux:table.column>N°</flux:table.column><flux:table.column>Réf.</flux:table.column><flux:table.column>Date</flux:table.column><flux:table.column>Client</flux:table.column><flux:table.column>Statut</flux:table.column>
                <flux:table.column align="end">TTC</flux:table.column><flux:table.column align="end">Reste</flux:table.column><flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($invoices as $invoice)
                    <flux:table.row :key="$invoice->id">
                        <flux:table.cell class="font-medium">{{ $invoice->number }} <span class="text-xs text-zinc-500">{{ $invoice->type->label() }}</span></flux:table.cell>
                        <flux:table.cell>
                            @if ($invoice->order)<flux:link :href="route('orders.show', $invoice->order)" wire:navigate>{{ $invoice->order->number }}</flux:link>@else<span class="text-zinc-500">{{ $invoice->sale?->number }}</span>@endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $invoice->issued_at->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell class="max-w-48 truncate">{{ $invoice->client?->displayName() ?? 'Client comptoir' }}</flux:table.cell>
                        <flux:table.cell><x-status-badge :status="$invoice->status" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$invoice->total" /></flux:table.cell>
                        <flux:table.cell align="end" @class(['font-medium', 'text-red-600' => $invoice->balanceDue() > 0 && $invoice->due_at?->isPast()])><x-money :amount="$invoice->balanceDue()" /></flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown align="end">
                                <flux:button size="xs" variant="ghost" icon="printer" />
                                <flux:menu>
                                    <flux:menu.item :href="route('print.invoice', [$invoice, 'a4'])" target="_blank">A4</flux:menu.item>
                                    <flux:menu.item :href="route('print.invoice', [$invoice, 'a5'])" target="_blank">A5</flux:menu.item>
                                    <flux:menu.item :href="route('print.invoice', [$invoice, 'ticket'])" target="_blank">Ticket 80 mm</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
