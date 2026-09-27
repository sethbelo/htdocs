<div class="space-y-6">
    <x-page-header title="Présence" subtitle="Arrivées, départs, retards, absences, heures supplémentaires et missions — par siège.">
        <x-slot:actions>
            <flux:select wire:model.live="siteId" size="sm" class="max-w-xs">@foreach ($this->accessibleSites() as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input type="date" wire:model.live="date" size="sm" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <x-kpi label="Présents" :value="$summary['present']" icon="check-circle" color="emerald" />
        <x-kpi label="Retards" :value="$summary['late']" icon="clock" color="amber" />
        <x-kpi label="Absents" :value="$summary['absent']" icon="x-circle" color="red" />
        <x-kpi label="Non pointés" :value="$summary['missing']" icon="question-mark-circle" color="zinc" />
        <x-kpi label="Heures sup." :value="$summary['overtime'].' h'" icon="bolt" color="purple" />
    </div>

    @can('attendance.create')
        <form wire:submit="scanBadge" class="panel flex items-center gap-2 p-3">
            <flux:icon.identification class="text-zinc-400" />
            <flux:input wire:model="badge" placeholder="Scanner un badge (RFID / NFC / QR)…" class="flex-1" />
            <flux:button type="submit">Pointer</flux:button>
        </form>
    @endcan

    <div class="panel p-4">
        <flux:table>
            <flux:table.columns><flux:table.column>Employé</flux:table.column><flux:table.column>Département</flux:table.column><flux:table.column>Arrivée</flux:table.column><flux:table.column>Départ</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column align="end">Travaillé</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
            <flux:table.rows>
                @foreach ($employees as $employee)
                    @php($record = $records[$employee->id] ?? null)
                    <flux:table.row :key="$employee->id">
                        <flux:table.cell class="font-medium">{{ $employee->fullName() }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->department?->name }}</flux:table.cell>
                        <flux:table.cell>{{ $record?->check_in ? substr($record->check_in, 0, 5) : '—' }} @if ($record?->late_minutes)<span class="text-xs text-amber-600">(+{{ $record->late_minutes }} min)</span>@endif</flux:table.cell>
                        <flux:table.cell>{{ $record?->check_out ? substr($record->check_out, 0, 5) : '—' }}</flux:table.cell>
                        <flux:table.cell>@if ($record)<x-status-badge :status="$record->status" />@else<flux:badge size="sm">Non pointé</flux:badge>@endif</flux:table.cell>
                        <flux:table.cell align="end">{{ $record ? round($record->worked_minutes / 60, 1).' h' : '' }} @if ($record?->overtime_minutes)<span class="text-xs text-purple-600">+{{ round($record->overtime_minutes / 60, 1) }} h</span>@endif</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                @can('attendance.create')
                                    @if (! $record?->check_in)<flux:button size="xs" icon="arrow-right-end-on-rectangle" wire:click="checkIn({{ $employee->id }})">Arrivée</flux:button>@elseif (! $record->check_out)<flux:button size="xs" icon="arrow-right-start-on-rectangle" wire:click="checkOut({{ $employee->id }})">Départ</flux:button>@endif
                                @endcan
                                @can('attendance.update')
                                    <flux:dropdown align="end">
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" />
                                        <flux:menu>@foreach (['absent', 'mission', 'leave'] as $st)<flux:menu.item wire:click="mark({{ $employee->id }}, '{{ $st }}')">{{ $statuses[$st] }}</flux:menu.item>@endforeach</flux:menu>
                                    </flux:dropdown>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
