<div class="space-y-6">
    <x-page-header title="Structure multi-sièges" subtitle="Sièges, succursales, boutiques, points de vente, entrepôts, caisses et salles d’impression." />
    @include('livewire.settings.nav')

    @foreach ([
        ['site', 'Sièges, succursales, boutiques & PDV', $sites],
        ['warehouse', 'Entrepôts', $warehouses],
        ['register', 'Caisses', $registers],
        ['room', 'Salles d’impression', $rooms],
    ] as [$entity, $title, $items])
        <div class="panel p-4">
            <div class="mb-2 flex items-center justify-between">
                <flux:heading>{{ $title }}</flux:heading>
                @can('sites.create')<flux:button size="sm" icon="plus" wire:click="edit('{{ $entity }}')">Ajouter</flux:button>@endcan
            </div>
            <flux:table>
                <flux:table.rows>
                    @foreach ($items as $item)
                        <flux:table.row :key="$entity.'-'.$item->id">
                            <flux:table.cell class="w-28 text-zinc-500">{{ $item->code }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $item->name }} @unless ($item->is_active)<flux:badge size="sm" color="red">Inactif</flux:badge>@endunless</flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                @if ($entity === 'site')
                                    <flux:badge size="sm" :color="$item->type->color()">{{ $item->type->label() }}</flux:badge> {{ $item->city }} · {{ $item->users_count }} utilisateur(s) · entrepôt principal : {{ $item->mainWarehouse?->name ?? '—' }}
                                @elseif ($entity === 'warehouse')
                                    {{ $item->site->name }} · responsable {{ $item->manager?->name ?? '—' }} · {{ $item->latitude ? 'géolocalisé' : 'sans coordonnées' }}
                                @elseif ($entity === 'register')
                                    {{ $item->site->name }} · stock : {{ $item->warehouse?->name }} · {{ $item->treasuryAccount?->name }} · {{ $item->requires_pin ? 'PIN requis' : 'sans PIN' }} · {{ $item->openSession ? 'ouverte par '.$item->openSession->cashier->name : 'fermée' }}
                                @else
                                    {{ $item->site->name }} · {{ $item->manager?->name }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="w-10">@can('sites.update')<flux:button size="xs" variant="ghost" icon="pencil" wire:click="edit('{{ $entity }}', {{ $item->id }})" />@endcan</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endforeach

    <flux:modal name="entity" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ ['site' => 'Siège / point de vente', 'warehouse' => 'Entrepôt', 'register' => 'Caisse', 'room' => 'Salle d’impression'][$entity] }}</flux:heading>
            <div class="grid gap-3 md:grid-cols-2">
                <flux:input wire:model="form.code" label="Code" />
                <flux:input wire:model="form.name" label="Nom" />
                @if ($entity === 'site')
                    <flux:select wire:model="form.type" label="Type">@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                    <flux:select wire:model="form.parent_id" label="Rattaché à"><flux:select.option value="">—</flux:select.option>@foreach ($sites as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:select wire:model="form.main_warehouse_id" label="Entrepôt principal"><flux:select.option value="">—</flux:select.option>@foreach ($warehouses as $w)<flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="form.phone" label="Téléphone" />
                    <flux:input wire:model="form.email" label="E-mail" />
                @else
                    <flux:select wire:model="form.site_id" label="Siège"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($sites as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if (in_array($entity, ['warehouse', 'room']))
                    <flux:select wire:model="form.manager_id" label="Responsable"><flux:select.option value="">—</flux:select.option>@foreach ($users as $u)<flux:select.option :value="$u->id">{{ $u->name }}</flux:select.option>@endforeach</flux:select>
                @endif
                @if ($entity === 'register')
                    <flux:select wire:model="form.warehouse_id" label="Entrepôt de déstockage"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($warehouses as $w)<flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:select wire:model="form.treasury_account_id" label="Compte caisse"><flux:select.option value="">—</flux:select.option>@foreach ($treasuryAccounts as $t)<flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="form.max_disbursement_without_approval" type="number" step="0.01" label="Décaissement max. sans responsable" />
                    <flux:switch wire:model="form.requires_pin" label="PIN requis à l’ouverture" />
                @endif
                @if (in_array($entity, ['site', 'warehouse']))
                    <flux:input wire:model="form.address" label="Adresse" />
                    <flux:input wire:model="form.city" label="Ville" />
                    <flux:input wire:model="form.latitude" type="number" step="any" label="Latitude (Mapbox)" />
                    <flux:input wire:model="form.longitude" type="number" step="any" label="Longitude (Mapbox)" />
                @endif
                <flux:switch wire:model="form.is_active" label="Actif" />
            </div>
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Enregistrer</flux:button></div>
        </form>
    </flux:modal>
</div>
