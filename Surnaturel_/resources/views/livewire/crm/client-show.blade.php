<div class="space-y-6">
    <x-page-header :title="$client->displayName()" :subtitle="$client->number.' · '.$client->type->label().' · '.strtoupper($client->segment)">
        <x-slot:actions>
            @can('orders.create')<flux:button size="sm" icon="plus" :href="route('orders.create', ['client' => $client->id])" wire:navigate variant="primary">Nouvelle commande</flux:button>@endcan
            @if ($client->loyaltyAccount)
                <flux:button size="sm" icon="credit-card" :href="route('print.loyalty-card', $client)" target="_blank">Carte fidélité</flux:button>
            @else
                @can('loyalty.create')<flux:button size="sm" icon="gift" wire:click="enrollLoyalty">Inscrire à la fidélité</flux:button>@endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-kpi label="Solde dû" :value="\App\Services\Support\Money::format($balance)" icon="document-currency-dollar" :color="$balance > 0 ? 'amber' : 'emerald'" />
        <x-kpi label="Chiffre d’affaires cumulé" :value="\App\Services\Support\Money::format($lifetime)" icon="banknotes" color="indigo" />
        <x-kpi label="Commandes" :value="$orders->count()" icon="clipboard-document-list" color="sky" />
        <x-kpi label="Fidélité" :value="$client->loyaltyAccount ? $client->loyaltyAccount->points_balance.' pts' : '—'" :hint="$client->loyaltyAccount?->tier?->name" icon="gift" color="purple" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel p-4 space-y-2 text-sm">
            <flux:heading>Coordonnées</flux:heading>
            <div><span class="text-zinc-500">Téléphone :</span> {{ $client->phone ?? '—' }}</div>
            <div><span class="text-zinc-500">WhatsApp :</span> {{ $client->whatsapp ?? '—' }}</div>
            <div><span class="text-zinc-500">E-mail :</span> {{ $client->email ?? '—' }}</div>
            <div><span class="text-zinc-500">Adresse :</span> {{ $client->address }} {{ $client->city }}</div>
            <div><span class="text-zinc-500">NIF :</span> {{ $client->tax_id ?? '—' }}</div>
            <div><span class="text-zinc-500">Commercial :</span> {{ $client->salesRep?->name ?? '—' }}</div>
            <div><span class="text-zinc-500">Crédit :</span> {{ $client->allow_credit ? 'autorisé jusqu’à '.\App\Services\Support\Money::format($client->credit_limit) : 'non autorisé' }}</div>
            @if ($client->notes)<flux:callout icon="information-circle" :text="$client->notes" class="mt-2" />@endif
        </div>

        <div class="panel p-4 xl:col-span-2">
            <div class="flex gap-1 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                @foreach (['orders' => 'Commandes', 'invoices' => 'Factures', 'payments' => 'Paiements', 'sales' => 'Achats boutique', 'loyalty' => 'Fidélité', 'documents' => 'Documents', 'notes' => 'Notes'] as $key => $label)
                    <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</flux:button>
                @endforeach
            </div>

            <div class="mt-3">
                @if ($tab === 'orders')
                    <flux:table>
                        <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Date</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Total</flux:table.column><flux:table.column align="end">Reste</flux:table.column></flux:table.columns>
                        <flux:table.rows>
                            @foreach ($orders as $order)
                                <flux:table.row :key="$order->id">
                                    <flux:table.cell><flux:link :href="route('orders.show', $order)" wire:navigate>{{ $order->number }}</flux:link></flux:table.cell>
                                    <flux:table.cell>{{ $order->ordered_at->format('d/m/Y') }}</flux:table.cell>
                                    <flux:table.cell><x-status-badge :status="$order->status" /></flux:table.cell>
                                    <flux:table.cell align="end"><x-money :amount="$order->total" /></flux:table.cell>
                                    <flux:table.cell align="end"><x-money :amount="$order->balanceDue()" /></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @elseif ($tab === 'invoices')
                    <flux:table>
                        <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Date</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Total</flux:table.column><flux:table.column align="end">Reste</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                        <flux:table.rows>
                            @foreach ($invoices as $invoice)
                                <flux:table.row :key="$invoice->id">
                                    <flux:table.cell>{{ $invoice->number }}</flux:table.cell>
                                    <flux:table.cell>{{ $invoice->issued_at->format('d/m/Y') }}</flux:table.cell>
                                    <flux:table.cell><x-status-badge :status="$invoice->status" /></flux:table.cell>
                                    <flux:table.cell align="end"><x-money :amount="$invoice->total" /></flux:table.cell>
                                    <flux:table.cell align="end"><x-money :amount="$invoice->balanceDue()" /></flux:table.cell>
                                    <flux:table.cell><flux:button size="xs" variant="ghost" icon="printer" :href="route('print.invoice', [$invoice, 'a4'])" target="_blank" /></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @elseif ($tab === 'payments')
                    <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($payments as $payment)
                            <li class="flex justify-between py-2" wire:key="py-{{ $payment->id }}"><span>{{ $payment->paid_at->format('d/m/Y H:i') }} · {{ $payment->method->label() }} · {{ $payment->number }}</span><x-money :amount="$payment->amount" /></li>
                        @empty<li class="py-2 text-zinc-500">Aucun paiement.</li>@endforelse
                    </ul>
                @elseif ($tab === 'sales')
                    <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($sales as $sale)
                            <li class="flex justify-between py-2" wire:key="sl-{{ $sale->id }}"><span>{{ $sale->sold_at->format('d/m/Y H:i') }} · {{ $sale->number }} · +{{ $sale->loyalty_points_earned }} pts</span><x-money :amount="$sale->total" /></li>
                        @empty<li class="py-2 text-zinc-500">Aucun achat boutique.</li>@endforelse
                    </ul>
                @elseif ($tab === 'loyalty')
                    @if ($client->loyaltyAccount)
                        <div class="mb-3 text-sm">Carte <strong>{{ $client->loyaltyAccount->card_number }}</strong> · niveau {{ $client->loyaltyAccount->tier?->name }} · {{ $client->loyaltyAccount->lifetime_points }} points cumulés</div>
                        <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            @foreach ($client->loyaltyAccount->transactions as $transaction)
                                <li class="flex justify-between py-2" wire:key="lt-{{ $transaction->id }}"><span>{{ $transaction->created_at->format('d/m/Y') }} · {{ $transaction->type->label() }} · {{ $transaction->description }}</span><span @class(['font-medium', 'text-emerald-600' => $transaction->points > 0, 'text-red-600' => $transaction->points < 0])>{{ $transaction->points > 0 ? '+' : '' }}{{ $transaction->points }}</span></li>
                            @endforeach
                        </ul>
                    @else
                        <x-empty-state icon="gift" title="Pas encore inscrit au programme de fidélité" />
                    @endif
                @elseif ($tab === 'documents')
                    <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($client->documents as $doc)
                            <li class="flex justify-between py-2" wire:key="doc-{{ $doc->id }}"><span>{{ $doc->title }} · v{{ $doc->current_version }}</span>
                                @if ($doc->latestVersion)<flux:button size="xs" variant="ghost" icon="arrow-down-tray" :href="app(\App\Services\Documents\DocumentService::class)->temporaryUrl($doc->latestVersion)" />@endif
                            </li>
                        @empty<li class="py-2 text-zinc-500">Aucun document.</li>@endforelse
                    </ul>
                    @can('documents.create')
                        <form wire:submit="uploadDocument" class="mt-3 flex items-center gap-2">
                            <input type="file" wire:model="document" class="text-sm" />
                            <flux:button type="submit" size="sm" icon="arrow-up-tray">Ajouter</flux:button>
                        </form>
                    @endcan
                @else
                    <form wire:submit="addNote" class="space-y-2">
                        <flux:textarea wire:model="note" rows="2" placeholder="Note interne (visible par l’équipe)…" />
                        <flux:button type="submit" size="sm">Ajouter la note</flux:button>
                    </form>
                    <ul class="mt-3 space-y-2">
                        @foreach ($client->comments as $comment)
                            <li class="rounded-lg bg-zinc-50 p-2 text-sm dark:bg-zinc-800" wire:key="cn-{{ $comment->id }}"><div class="text-xs text-zinc-500">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>{{ $comment->body }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
