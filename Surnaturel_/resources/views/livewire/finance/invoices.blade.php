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
        <div class="grid items-end gap-3 md:grid-cols-7">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N° facture, n° commande, client…" class="md:col-span-2" />
            <flux:select wire:model.live="status"><flux:select.option value="">Tous statuts</flux:select.option>@foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
            <flux:input type="date" wire:model.live="from" />
            <flux:input type="date" wire:model.live="to" />
            <flux:select wire:model.live="type"><flux:select.option value="">Tous types</flux:select.option>@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
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
                            <div class="flex justify-end gap-1">
                                @if ($invoice->balanceDue() > 0 && $invoice->status->value !== 'cancelled' && $invoice->type->value !== 'credit_note')
                                    @if ($invoice->order)
                                        @can('cash.collect')<x-edit-button label="Encaisser" icon="banknotes" :href="route('cash.checkout', ['commande' => $invoice->order->number])" />@endcan
                                    @else
                                        @can('payments.create')<x-edit-button label="Encaisser" icon="banknotes" wire:click="openAction({{ $invoice->id }}, 'collect')" />@endcan
                                    @endif
                                @endif
                                <flux:dropdown align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="printer" :href="route('print.invoice', [$invoice, 'a4'])" target="_blank">Imprimer A4</flux:menu.item>
                                        <flux:menu.item icon="printer" :href="route('print.invoice', [$invoice, 'a5'])" target="_blank">Imprimer A5</flux:menu.item>
                                        <flux:menu.item icon="receipt-percent" :href="route('print.invoice', [$invoice, 'ticket'])" target="_blank">Ticket 80 mm</flux:menu.item>
                                        @if ($invoice->status->value !== 'cancelled' && $invoice->type->value !== 'credit_note')
                                            @can('invoices.cancel')
                                                <flux:menu.separator />
                                                @if ($invoice->balanceDue() > 0)<flux:menu.item icon="document-minus" wire:click="openAction({{ $invoice->id }}, 'credit')">Émettre un avoir</flux:menu.item>@endif
                                                @if ((float) $invoice->amount_paid <= 0)<flux:menu.item icon="x-circle" variant="danger" wire:click="openAction({{ $invoice->id }}, 'cancel')">Annuler la facture</flux:menu.item>@endif
                                            @endcan
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="invoice-action" class="w-full max-w-lg">
        @if ($current)
            <form wire:submit="runAction" class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ ['collect' => 'Encaisser la facture', 'credit' => 'Émettre un avoir', 'cancel' => 'Annuler la facture'][$action['kind']] ?? '' }}</flux:heading>
                    <flux:text>{{ $current->number }} · {{ $current->client?->displayName() ?? 'Client comptoir' }} · reste dû <strong><x-money :amount="$current->balanceDue()" /></strong></flux:text>
                </div>
                @if ($action['kind'] === 'collect')
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input wire:model="action.amount" type="number" step="0.01" label="Montant" />
                        <flux:select wire:model="action.method" label="Moyen de paiement">@foreach ($methods as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                        <flux:select wire:model="action.treasury_account_id" label="Compte crédité" class="col-span-2"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($treasuryAccounts as $a)<flux:select.option :value="$a->id">{{ $a->name }} ({{ $a->type->label() }})</flux:select.option>@endforeach</flux:select>
                        <flux:input wire:model="action.reference" label="Référence (n° virement, chèque…)" class="col-span-2" />
                    </div>
                @else
                    @if ($action['kind'] === 'credit')<flux:input wire:model="action.amount" type="number" step="0.01" label="Montant de l’avoir (TTC)" />@endif
                    <flux:textarea wire:model="action.reason" label="Motif" rows="2" required />
                    <flux:callout icon="information-circle" :text="$action['kind'] === 'cancel' ? 'Un avoir du montant total est généré et comptabilisé (extourne de la vente et de la TVA).' : 'L’avoir réduit la créance du client ; la vente et la TVA sont extournées au prorata.'" />
                @endif
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Fermer</flux:button></flux:modal.close>
                    <flux:button type="submit" :variant="$action['kind'] === 'cancel' ? 'danger' : 'primary'">Valider</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
