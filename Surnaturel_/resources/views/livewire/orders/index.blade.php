<div class="space-y-6">
    <x-page-header title="Commandes d’impression" subtitle="Toutes les commandes, de la prise de commande à la clôture.">
        <x-slot:actions>
            @can('orders.export')
                <flux:button wire:click="export" icon="arrow-down-tray" size="sm">Excel</flux:button>
            @endcan
            @can('orders.create')
                <flux:button :href="route('orders.create')" wire:navigate variant="primary" icon="plus" size="sm">Nouvelle commande</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="panel p-4 space-y-4">
        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-7">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N° commande, client, téléphone…" class="xl:col-span-2" />
            <flux:select wire:model.live="status" placeholder="Tous statuts">
                <flux:select.option value="">Tous statuts</flux:select.option>
                @foreach ($statuses as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="priority">
                <flux:select.option value="">Toutes priorités</flux:select.option>
                @foreach ($priorities as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="salesRepId">
                <flux:select.option value="">Tous commerciaux</flux:select.option>
                @foreach ($salesReps as $rep)
                    <flux:select.option :value="$rep->id">{{ $rep->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model.live="from" />
            <flux:input type="date" wire:model.live="to" />
        </div>
        @if (count($this->accessibleSites()) > 1)
            <flux:select wire:model.live="siteId" class="max-w-xs">
                <flux:select.option value="">Tous les sièges</flux:select.option>
                @foreach ($this->accessibleSites() as $site)
                    <flux:select.option :value="$site->id">{{ $site->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:table :paginate="$orders">
            <flux:table.columns>
                <flux:table.column>N° commande</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Client</flux:table.column>
                <flux:table.column>Commercial</flux:table.column>
                <flux:table.column>Délai</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column align="end">Total</flux:table.column>
                <flux:table.column align="end">Reste à payer</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($orders as $order)
                    <flux:table.row :key="$order->id">
                        <flux:table.cell>
                            <flux:link :href="route('orders.show', $order)" wire:navigate class="font-medium">{{ $order->number }}</flux:link>
                            @if (in_array($order->priority->value, ['urgent', 'high']))
                                <flux:badge size="sm" :color="$order->priority->color()" class="ms-1">{{ $order->priority->label() }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $order->ordered_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell class="max-w-56 truncate">{{ $order->client->displayName() }}</flux:table.cell>
                        <flux:table.cell>{{ $order->salesRep?->name }}</flux:table.cell>
                        <flux:table.cell @class(['whitespace-nowrap', 'text-red-600 font-medium' => $order->isOverdue()])>{{ $order->due_at?->format('d/m H:i') ?? '—' }}</flux:table.cell>
                        <flux:table.cell><x-status-badge :status="$order->status" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$order->total" /></flux:table.cell>
                        <flux:table.cell align="end" @class(['font-medium', 'text-amber-600' => $order->balanceDue() > 0])><x-money :amount="$order->balanceDue()" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8"><x-empty-state icon="clipboard-document-list" title="Aucune commande" text="Modifiez les filtres ou créez une nouvelle commande." /></flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
