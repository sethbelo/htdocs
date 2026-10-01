<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="$label.' '.$document->number" :eyebrow="$document->contact->display_name" :back="route($backRoute)">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[13px] text-fg-muted">
                <x-ui.status map="document" :value="$document->status" />
                <span>Émis le {{ $document->issue_date->format('d/m/Y') }}</span>
                @if ($document->creator)<span>· par {{ $document->creator->name }}</span>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('pdf.document', ['document' => $document, 'download' => 1]) }}" class="btn btn-secondary"><x-ui.icon name="arrow-down-tray" class="size-[18px]" /> Télécharger</a>
            <a href="{{ route('pdf.document', $document) }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="printer" class="size-[18px]" /> Imprimer</a>
            @if ($document->type->value === 'quote')
                <a href="{{ route('pdf.document', ['document' => $document, 'format' => 'proforma', 'modele' => $template]) }}" target="_blank" class="btn btn-secondary" title="Même contenu, intitulé FACTURE PRO FORMA"><x-ui.icon name="document-duplicate" class="size-[18px]" /> Imprimer en pro forma</a>
            @endif
            @if ($canManage && (float) $document->amount_paid == 0 && ! in_array($document->status->value, ['accepted', 'paid', 'cancelled']))
                <x-ui.button variant="primary" icon="pencil-square" :href="route('documents.edit', $document)">Modifier</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        {{-- ═══ 2. APERÇU PDF ════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-2.5">
                <p class="text-[13px] font-semibold">Aperçu</p>
                <div class="segmented">
                    @foreach (config('erp.document_templates') as $key => $tpl)
                        <button type="button" wire:click="$set('template', '{{ $key }}')" aria-pressed="{{ $template === $key ? 'true' : 'false' }}">{{ $tpl['label'] }}</button>
                    @endforeach
                </div>
            </div>
            <iframe wire:key="preview-{{ $template }}-{{ $document->updated_at?->timestamp }}" src="{{ route('pdf.document', ['document' => $document, 'modele' => $template]) }}#toolbar=0&navpanes=0&view=FitH" class="h-[78vh] w-full bg-ink-100" title="Aperçu du document"></iframe>
        </div>

        {{-- ═══ 3. ACTIONS & INFORMATIONS ════════════════════════════════════ --}}
        <aside class="space-y-6">
            <x-ui.card title="Montants" icon="calculator">
                <dl class="space-y-2 text-[13.5px]">
                    <div class="flex justify-between"><dt class="text-fg-muted">Total HT</dt><dd class="tabular-nums">{{ money((float) $document->subtotal - (float) $document->discount_total, $document->currency_code) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-muted">TVA</dt><dd class="tabular-nums">{{ money($document->tax_total, $document->currency_code) }}</dd></div>
                    <div class="flex items-baseline justify-between border-t border-line pt-2.5"><dt class="font-semibold">Total TTC</dt><dd class="font-display text-[22px] font-semibold tabular-nums">{{ money($document->total, $document->currency_code) }}</dd></div>
                    @if ($type === 'invoice')
                        <div class="flex justify-between"><dt class="text-fg-muted">Encaissé</dt><dd class="font-semibold tabular-nums text-emerald-600">{{ money($document->amount_paid, $document->currency_code) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-fg-muted">Reste dû</dt><dd @class(['font-semibold tabular-nums', 'text-flame-600' => $document->balance_due > 0])>{{ money($document->balance_due, $document->currency_code) }}</dd></div>
                    @endif
                </dl>
            </x-ui.card>

            <x-ui.card title="Actions" icon="bolt">
                <div class="grid gap-2">
                    @if ($order)
                        <x-ui.button variant="soft" icon="arrow-path-rounded-square" :href="route('orders.show', $order)" class="justify-start">Dossier {{ $order->number }} · {{ erp_status('order_stage', $order->stage)[0] }}</x-ui.button>
                    @endif
                    @if ($canManage)
                        @if (in_array($document->status->value, ['draft']))
                            <x-ui.button variant="secondary" icon="paper-airplane" wire:click="markSent" class="justify-start">Marquer comme envoyé</x-ui.button>
                        @endif
                        @if ($type === 'quote' && in_array($document->status->value, ['draft', 'sent', 'viewed']))
                            <x-ui.button variant="primary" icon="check-badge" wire:click="accept" wire:confirm="Enregistrer l’accord du client ?" class="justify-start">Accepté par le client</x-ui.button>
                            <x-ui.button variant="ghost" icon="x-circle" wire:click="reject" wire:confirm="Marquer ce devis comme refusé ?" class="justify-start">Refusé par le client</x-ui.button>
                        @endif
                        @if ($type === 'quote' && ! $order && $document->status->value !== 'rejected' && auth()->user()->canManage('orders'))
                            <x-ui.button variant="accent" icon="arrow-path-rounded-square" wire:click="$set('showOrder', true)" class="justify-start">Ouvrir un dossier de commande</x-ui.button>
                        @endif
                        <x-ui.button variant="ghost" icon="document-duplicate" wire:click="duplicate" class="justify-start">Dupliquer</x-ui.button>
                    @endif
                    @if ($type === 'invoice' && $document->balance_due > 0 && $document->status->value !== 'cancelled' && auth()->user()->canManage('cash'))
                        <x-ui.button variant="primary" icon="banknotes" wire:click="openPayment" class="justify-start">Enregistrer un paiement</x-ui.button>
                    @endif
                    @if ($type === 'purchase_order' && data_get($document->meta, 'reception') !== 'complete' && auth()->user()->canManage('purchases'))
                        <x-ui.button variant="primary" icon="inbox-arrow-down" wire:click="openReceive" class="justify-start">Réceptionner la marchandise</x-ui.button>
                    @endif
                    @if ($canManage && (float) $document->amount_paid == 0 && (! $order || $order->stage === 'devis'))
                        <x-ui.button variant="ghost" icon="trash" wire:click="delete" wire:confirm="Supprimer définitivement ce document ?" class="justify-start text-flame-600">Supprimer</x-ui.button>
                    @endif
                </div>
            </x-ui.card>

            @if ($type === 'invoice' && $document->allocations->isNotEmpty())
                <x-ui.card title="Règlements" icon="banknotes" :padded="false">
                    <ul>
                        @foreach ($document->allocations as $allocation)
                            <li class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 text-[13px] last:border-0">
                                <div><p class="font-semibold">{{ $allocation->payment?->method?->label() }}</p><p class="text-[12px] text-fg-subtle">{{ $allocation->payment?->paid_at?->format('d/m/Y H:i') }} · {{ $allocation->payment?->reference }}</p></div>
                                <span class="flex items-center gap-2">
                                    <span class="font-semibold tabular-nums text-emerald-600">{{ money($allocation->amount, $document->currency_code) }}</span>
                                    @if ($allocation->payment)<a href="{{ route('pdf.receipt', $allocation->payment) }}" target="_blank" class="btn btn-ghost btn-sm" title="Imprimer le reçu"><x-ui.icon name="printer" class="size-4" />Reçu</a>@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            @if ($type === 'purchase_order')
                <x-ui.card title="Réception" icon="inbox-arrow-down" :padded="false">
                    <ul>
                        @foreach ($document->items as $item)
                            <li class="border-b border-line px-5 py-3 text-[13px] last:border-0">
                                <p class="truncate font-medium">{{ $item->description }}</p>
                                <x-ui.progress class="mt-1.5" :value="(float) $item->quantity ? round((float) $item->received_quantity / (float) $item->quantity * 100) : 0" :label="qty($item->received_quantity).' / '.qty($item->quantity).' '.$item->unit" tone="lagoon" />
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </aside>
    </div>

    {{-- ═══ 4. FENÊTRES ══════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showPayment" title="Enregistrer un paiement" :subtitle="$document->number" icon="banknotes">
        <div class="space-y-4">
            <x-ui.input label="Montant" type="number" step="0.01" wire:model="paymentAmount" suffix="{{ $document->currency_code }}" required />
            <x-ui.select label="Mode" wire:model="paymentMethod" :options="['cash' => 'Espèces', 'mobile_money' => 'Mobile money', 'bank_transfer' => 'Virement', 'card' => 'Carte', 'cheque' => 'Chèque']" />
            <x-ui.select label="Compte" wire:model="paymentAccountId" :options="$accounts->pluck('name', 'id')" placeholder="Choisir…" />
            <x-ui.input label="Référence" wire:model="paymentReference" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="recordPayment">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showOrder" title="Ouvrir un dossier de commande" icon="arrow-path-rounded-square">
        <div class="space-y-4">
            <x-ui.select label="Nature de la commande" wire:model="orderKind" :options="config('erp.order_kinds')" />
            <x-ui.input label="Objet" wire:model="orderTitle" :placeholder="$document->items->first()?->description" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="openOrder">Ouvrir le dossier</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showReceive" title="Réception de marchandise" :subtitle="$document->number" icon="inbox-arrow-down" size="lg">
        <x-ui.select label="Entrepôt de réception" wire:model="receiveWarehouseId" :options="$warehouses->pluck('name', 'id')" />
        <table class="table mt-5 min-w-0">
            <thead><tr><th>Article</th><th class="num">Commandé</th><th class="num">Déjà reçu</th><th class="num w-32">Reçu ce jour</th></tr></thead>
            <tbody>
                @foreach ($document->items as $item)
                    <tr>
                        <td class="font-medium">{{ $item->description }}</td>
                        <td class="num">{{ qty($item->quantity) }}</td>
                        <td class="num text-fg-muted">{{ qty($item->received_quantity) }}</td>
                        <td><input type="number" step="0.01" min="0" wire:model="received.{{ $item->id }}" class="input text-right"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="receive">Valider la réception</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
