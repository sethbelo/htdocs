<div class="space-y-6">
    <x-page-header title="Approvisionnement & achats" subtitle="Demande d’achat → Validation → Bon de commande → Réception → Contrôle → Entrée stock → Facture → Paiement">
        <x-slot:actions>
            @can('purchases.create')
                <flux:button size="sm" icon="document-plus" wire:click="newRequest">Demande d’achat</flux:button>
                <flux:button size="sm" variant="primary" icon="shopping-bag" wire:click="newOrder">Bon de commande</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="flex gap-2">
        @foreach (['requests' => 'Demandes d’achat', 'orders' => 'Bons de commande', 'invoices' => 'Factures fournisseurs'] as $key => $label)
            <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</flux:button>
        @endforeach
    </div>

    <div class="panel p-4">
        @if ($tab === 'requests')
            <flux:table>
                <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Demandeur</flux:table.column><flux:table.column>Justification</flux:table.column><flux:table.column>Besoin le</flux:table.column><flux:table.column>Statut / étape</flux:table.column><flux:table.column align="end">Estimation</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse ($requests as $request)
                        <flux:table.row :key="$request->id">
                            <flux:table.cell class="font-medium">{{ $request->number }}</flux:table.cell>
                            <flux:table.cell>{{ $request->requester->name }}</flux:table.cell>
                            <flux:table.cell class="max-w-64 truncate">{{ $request->justification }} <span class="text-xs text-zinc-500">({{ $request->items->count() }} ligne·s)</span></flux:table.cell>
                            <flux:table.cell>{{ $request->needed_by?->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell><x-status-badge :status="$request->status" /> @if ($request->approvalRequest?->currentStep)<span class="text-xs text-zinc-500">· {{ $request->approvalRequest->currentStep->name }}</span>@endif</flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$request->estimated_total" /></flux:table.cell>
                            <flux:table.cell>@if ($request->status->value === 'approved')@can('purchases.create')<flux:button size="xs" icon="arrow-right" wire:click="newOrder({{ $request->id }})">Commander</flux:button>@endcan @endif</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="7"><x-empty-state title="Aucune demande d’achat" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @elseif ($tab === 'orders')
            <flux:table>
                <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Fournisseur</flux:table.column><flux:table.column>Entrepôt</flux:table.column><flux:table.column>Prévu le</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Total</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse ($orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell class="font-medium">{{ $order->number }}</flux:table.cell>
                            <flux:table.cell>{{ $order->supplier->name }}</flux:table.cell>
                            <flux:table.cell>{{ $order->warehouse->name }}</flux:table.cell>
                            <flux:table.cell>{{ $order->expected_on?->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell><x-status-badge :status="$order->status" /> <span class="text-xs text-zinc-500">{{ $order->receipts->count() }} BR · {{ $order->supplierInvoices->count() }} FF</span></flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$order->total" /></flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-1">
                                    @if (in_array($order->status->value, ['sent', 'partially_received']))@can('purchases.update')<flux:button size="xs" icon="inbox-arrow-down" wire:click="openReception({{ $order->id }})">Réceptionner</flux:button>@endcan @endif
                                    @can('purchases.validate')<flux:button size="xs" icon="document-text" wire:click="openInvoice({{ $order->id }})">Facture</flux:button>@endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="7"><x-empty-state title="Aucun bon de commande" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @else
            <flux:table>
                <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Réf. fournisseur</flux:table.column><flux:table.column>Fournisseur</flux:table.column><flux:table.column>Date / échéance</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Total</flux:table.column><flux:table.column align="end">Reste</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse ($invoices as $invoice)
                        <flux:table.row :key="$invoice->id">
                            <flux:table.cell class="font-medium">{{ $invoice->number }}</flux:table.cell>
                            <flux:table.cell>{{ $invoice->supplier_reference }}</flux:table.cell>
                            <flux:table.cell>{{ $invoice->supplier->name }}</flux:table.cell>
                            <flux:table.cell>{{ $invoice->invoice_date->format('d/m/Y') }} → <span @class(['text-red-600' => $invoice->due_date?->isPast() && $invoice->balanceDue() > 0])>{{ $invoice->due_date?->format('d/m/Y') }}</span></flux:table.cell>
                            <flux:table.cell><flux:badge size="sm">{{ ['pending' => 'À payer', 'partially_paid' => 'Partiel', 'paid' => 'Payée', 'cancelled' => 'Annulée'][$invoice->status] ?? $invoice->status }}</flux:badge></flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$invoice->total" /></flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$invoice->balanceDue()" /></flux:table.cell>
                            <flux:table.cell>@if ($invoice->balanceDue() > 0)@can('treasury.disburse')<flux:button size="xs" icon="banknotes" wire:click="openPayment({{ $invoice->id }})">Payer</flux:button>@endcan @endif</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="8"><x-empty-state title="Aucune facture fournisseur" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="request" class="w-full max-w-2xl">
        <form wire:submit="saveRequest" class="space-y-4">
            <flux:heading size="lg">Demande d’achat</flux:heading>
            <flux:textarea wire:model="request.justification" label="Justification" rows="2" required />
            <flux:input wire:model="request.needed_by" type="date" label="Besoin pour le" />
            @foreach ($requestItems as $i => $item)
                <div class="grid grid-cols-12 gap-2" wire:key="ri-{{ $i }}">
                    <flux:select wire:model="requestItems.{{ $i }}.product_id" class="col-span-4"><flux:select.option value="">Article…</flux:select.option>@foreach ($products as $p)<flux:select.option :value="$p->id">{{ $p->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="requestItems.{{ $i }}.description" placeholder="Désignation" class="col-span-4" />
                    <flux:input wire:model="requestItems.{{ $i }}.quantity" type="number" step="any" placeholder="Qté" class="col-span-2" />
                    <flux:input wire:model="requestItems.{{ $i }}.estimated_unit_price" type="number" step="0.01" placeholder="P.U. estimé" class="col-span-2" />
                </div>
            @endforeach
            <flux:button type="button" size="sm" icon="plus" wire:click="addRequestItem">Ligne</flux:button>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="saveDraftRequest">Brouillon</flux:button>
                <flux:button type="submit" variant="primary">Soumettre à validation</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="po" class="w-full max-w-3xl">
        <form wire:submit="saveOrder" class="space-y-4">
            <flux:heading size="lg">Bon de commande fournisseur</flux:heading>
            <div class="grid gap-3 md:grid-cols-3">
                <flux:select wire:model="po.supplier_id" label="Fournisseur"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($suppliers as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="po.warehouse_id" label="Entrepôt de livraison">@foreach ($warehouses as $w)<flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="po.expected_on" type="date" label="Livraison prévue" />
            </div>
            @foreach ($poItems as $i => $item)
                <div class="grid grid-cols-12 gap-2" wire:key="poi-{{ $i }}">
                    <flux:select wire:model="poItems.{{ $i }}.product_id" class="col-span-5"><flux:select.option value="">Article…</flux:select.option>@foreach ($products as $p)<flux:select.option :value="$p->id">{{ $p->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="poItems.{{ $i }}.quantity" type="number" step="any" placeholder="Qté" class="col-span-2" />
                    <flux:input wire:model="poItems.{{ $i }}.unit_price" type="number" step="0.01" placeholder="P.U. HT" class="col-span-3" />
                    <flux:input wire:model="poItems.{{ $i }}.tax_rate" type="number" step="0.01" placeholder="TVA" class="col-span-2" />
                </div>
            @endforeach
            <flux:error name="poItems.*.product_id" />
            <flux:button type="button" size="sm" icon="plus" wire:click="addPoItem">Ligne</flux:button>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Émettre le bon de commande</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="reception" class="w-full max-w-2xl">
        @if ($receivingOrder)
            <form wire:submit="receive" class="space-y-4">
                <flux:heading size="lg">Réception & contrôle — {{ $receivingOrder->number }}</flux:heading>
                @foreach ($receivingOrder->items as $item)
                    <div class="grid grid-cols-12 items-end gap-2" wire:key="rc-{{ $item->id }}">
                        <div class="col-span-4 text-sm"><div class="font-medium">{{ $item->description }}</div><div class="text-xs text-zinc-500">Commandé {{ (float) $item->quantity }} · reçu {{ (float) $item->received_quantity }}</div></div>
                        <flux:input wire:model="reception.{{ $item->id }}.accepted" type="number" step="any" label="Accepté" class="col-span-2" />
                        <flux:input wire:model="reception.{{ $item->id }}.rejected" type="number" step="any" label="Refusé" class="col-span-2" />
                        <flux:input wire:model="reception.{{ $item->id }}.reason" label="Motif refus" class="col-span-4" />
                    </div>
                @endforeach
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary">Valider la réception</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    <flux:modal name="invoice" class="w-full max-w-lg">
        <form wire:submit="saveInvoice" class="space-y-4">
            <flux:heading size="lg">Facture fournisseur</flux:heading>
            <flux:input wire:model="invoice.supplier_reference" label="Référence fournisseur" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="invoice.total" type="number" step="0.01" label="Total TTC" />
                <flux:input wire:model="invoice.tax_total" type="number" step="0.01" label="dont TVA" />
                <flux:input wire:model="invoice.invoice_date" type="date" label="Date facture" />
                <flux:input wire:model="invoice.due_date" type="date" label="Échéance" />
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Enregistrer & comptabiliser</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="payment" class="w-full max-w-lg">
        <form wire:submit="pay" class="space-y-4">
            <flux:heading size="lg">Paiement fournisseur</flux:heading>
            <flux:select wire:model="payment.treasury_account_id" label="Compte de trésorerie"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($treasuryAccounts as $t)<flux:select.option :value="$t->id">{{ $t->name }} ({{ \App\Services\Support\Money::format($t->balance) }})</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="payment.method" label="Moyen">@foreach ($methods as $v => $l)@if ($v !== 'credit')<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endif @endforeach</flux:select>
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="payment.amount" type="number" step="0.01" label="Montant" />
                <flux:input wire:model="payment.reference" label="Référence" />
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Payer</flux:button>
        </form>
    </flux:modal>
</div>
