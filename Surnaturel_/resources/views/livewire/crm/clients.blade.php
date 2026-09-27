<div class="space-y-6">
    <x-page-header title="Clients" subtitle="Particuliers, entreprises, administrations, ONG, revendeurs — B2B et B2C.">
        <x-slot:actions>
            @can('clients.create')<flux:button size="sm" variant="primary" icon="user-plus" wire:click="create">Nouveau client</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="panel p-4 space-y-4">
        <div class="grid gap-3 md:grid-cols-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, n° client, téléphone, e-mail…" class="md:col-span-2" />
            <flux:select wire:model.live="type">
                <flux:select.option value="">Tous types</flux:select.option>
                @foreach ($types as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model.live="segment">
                <flux:select.option value="">B2B & B2C</flux:select.option>
                <flux:select.option value="b2b">B2B</flux:select.option>
                <flux:select.option value="b2c">B2C</flux:select.option>
            </flux:select>
        </div>
        <flux:table :paginate="$clients">
            <flux:table.columns>
                <flux:table.column>N°</flux:table.column>
                <flux:table.column>Client</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Contact</flux:table.column>
                <flux:table.column>Commercial</flux:table.column>
                <flux:table.column>Fidélité</flux:table.column>
                <flux:table.column align="end">Commandes</flux:table.column>
                <flux:table.column align="end">Solde dû</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($clients as $client)
                    <flux:table.row :key="$client->id">
                        <flux:table.cell class="text-zinc-500">{{ $client->number }}</flux:table.cell>
                        <flux:table.cell><flux:link :href="route('clients.show', $client)" wire:navigate class="font-medium">{{ $client->displayName() }}</flux:link></flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $client->type->label() }}</flux:badge> <span class="text-xs uppercase text-zinc-500">{{ $client->segment }}</span></flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $client->phone }}<div class="text-xs text-zinc-500">{{ $client->email }}</div></flux:table.cell>
                        <flux:table.cell>{{ $client->salesRep?->name }}</flux:table.cell>
                        <flux:table.cell>@if ($client->loyaltyAccount)<flux:badge size="sm" :color="$client->loyaltyAccount->tier?->color ?? 'zinc'">{{ $client->loyaltyAccount->tier?->name }} · {{ $client->loyaltyAccount->points_balance }} pts</flux:badge>@endif</flux:table.cell>
                        <flux:table.cell align="end">{{ $client->orders_count }}</flux:table.cell>
                        <flux:table.cell align="end" @class(['text-amber-600 font-medium' => ($client->due - $client->paid_on_due) > 0])><x-money :amount="(float) $client->due - (float) $client->paid_on_due" /></flux:table.cell>
                        <flux:table.cell>@can('update', $client)<flux:button size="xs" variant="ghost" icon="pencil" wire:click="edit({{ $client->id }})" />@endcan</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="9"><x-empty-state icon="users" title="Aucun client" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @include('livewire.crm.partials.client-form')
</div>
