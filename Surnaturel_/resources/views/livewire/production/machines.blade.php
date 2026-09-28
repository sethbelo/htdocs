<div class="space-y-6">
    <x-page-header title="Parc machines" subtitle="Imprimantes, offset, traceurs, massicots, plastifieuses… maintenance préventive et corrective.">
        <x-slot:actions>
            @can('machines.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="create">Nouvelle machine</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="panel flex flex-wrap gap-2.5 p-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nom, code, marque, modèle…" size="sm" class="max-w-sm" />
        <flux:select wire:model.live="machineStatus" size="sm" class="max-w-48"><flux:select.option value="">Tous les états</flux:select.option>@foreach ($statuses as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
        <flux:select wire:model.live="machineType" size="sm" class="max-w-52"><flux:select.option value="">Tous les types</flux:select.option>@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($machines as $machine)
            <div class="panel p-4 space-y-3" wire:key="m-{{ $machine->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <flux:heading>{{ $machine->name }}</flux:heading>
                        <flux:text size="sm">{{ $machine->code }} · {{ $machine->type->label() }} · {{ $machine->brand }} {{ $machine->model }}</flux:text>
                    </div>
                    <x-status-badge :status="$machine->status" />
                </div>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <div><dt class="text-xs text-zinc-500">N° série</dt><dd>{{ $machine->serial_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Localisation</dt><dd>{{ $machine->printRoom?->name ?? $machine->site->name }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Compteur</dt><dd class="tabular-nums">{{ number_format($machine->counter, 0, ',', ' ') }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Dernière maintenance</dt><dd>{{ $machine->last_maintenance_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Prochaine</dt><dd @class(['font-medium text-amber-600' => $machine->isMaintenanceDue()])>{{ $machine->next_maintenance_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Coût maint. / immobilisation</dt><dd><x-money :amount="$machine->maintenances_sum_cost ?? 0" /> · {{ (float) $machine->maintenances_sum_downtime_hours }} h</dd></div>
                </dl>
                @foreach ($machine->maintenances->where('status', '!=', 'done') as $open)
                    <div class="rounded-lg bg-amber-50 p-2 text-sm dark:bg-amber-500/10" wire:key="mo-{{ $open->id }}">
                        <div class="font-medium">{{ $open->type->label() }} en cours depuis {{ $open->started_at?->diffForHumans() }}</div>
                        <div class="text-xs">{{ $open->description }}</div>
                        @can('machines.update')
                            <div class="mt-2 flex gap-2">
                                <flux:input wire:model="closingCost" type="number" step="0.01" size="xs" placeholder="Coût" class="w-28" />
                                <flux:button size="xs" wire:click="closeMaintenance({{ $open->id }})" icon="check">Clôturer</flux:button>
                            </div>
                        @endcan
                    </div>
                @endforeach
                <details class="text-sm">
                    <summary class="cursor-pointer text-zinc-500">Historique ({{ $machine->maintenances->count() }})</summary>
                    <ul class="mt-2 space-y-1">
                        @foreach ($machine->maintenances->take(6) as $m)
                            <li class="text-xs" wire:key="mh-{{ $m->id }}">{{ $m->started_at?->format('d/m/Y') }} · {{ $m->type->label() }} · <x-money :amount="$m->cost" /> · {{ $m->description }}</li>
                        @endforeach
                    </ul>
                </details>
                <div class="flex gap-2">
                    @can('machines.update')
                        <x-edit-button wire:click="edit({{ $machine->id }})" />
                        <flux:button size="sm" icon="wrench-screwdriver" wire:click="openMaintenance({{ $machine->id }})">Maintenance / panne</flux:button>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="machine" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Modifier la machine' : 'Nouvelle machine' }}</flux:heading>
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="form.code" label="Code" />
                <flux:input wire:model="form.name" label="Nom" class="md:col-span-2" />
                <flux:select wire:model="form.type" label="Type">
                    @foreach ($types as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
                </flux:select>
                <flux:input wire:model="form.brand" label="Marque" />
                <flux:input wire:model="form.model" label="Modèle" />
                <flux:input wire:model="form.serial_number" label="N° de série" />
                <flux:select wire:model="form.site_id" label="Siège">
                    @foreach ($sites as $site)<flux:select.option :value="$site->id">{{ $site->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="form.print_room_id" label="Salle">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach ($printRooms as $room)<flux:select.option :value="$room->id">{{ $room->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="form.status" label="État">
                    @foreach ($statuses as $value => $label)<flux:select.option :value="$value">{{ $label }}</flux:select.option>@endforeach
                </flux:select>
                <flux:input wire:model="form.counter" type="number" label="Compteur" />
                <flux:input wire:model="form.maintenance_interval_days" type="number" label="Périodicité maintenance (j)" />
                <flux:input wire:model="form.next_maintenance_at" type="date" label="Prochaine maintenance" />
                <flux:input wire:model="form.purchase_cost" type="number" step="0.01" label="Coût d’acquisition" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="maintenance" class="w-full max-w-lg">
        <form wire:submit="startMaintenance" class="space-y-4">
            <flux:heading size="lg">Maintenance / panne</flux:heading>
            <flux:radio.group wire:model="maintenance.type" variant="segmented" label="Type">
                @foreach ($maintenanceTypes as $value => $label)<flux:radio :value="$value" :label="$label" />@endforeach
            </flux:radio.group>
            <flux:input wire:model="maintenance.technician" label="Technicien / prestataire" />
            <flux:input wire:model="maintenance.scheduled_on" type="date" label="Date prévue" />
            <flux:textarea wire:model="maintenance.description" label="Description" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Ouvrir l’intervention</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
