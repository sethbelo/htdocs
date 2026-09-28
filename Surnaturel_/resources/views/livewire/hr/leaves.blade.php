<div class="space-y-6">
    <x-page-header title="Congés" subtitle="Employé → Responsable → RH → Validation">
        <x-slot:actions><flux:modal.trigger name="leave"><flux:button size="sm" variant="primary" icon="plus">Demander un congé</flux:button></flux:modal.trigger></x-slot:actions>
    </x-page-header>

    @if ($balances->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($balances as $row)<x-kpi :label="'Solde '.$row['type']->name" :value="$row['balance'].' j'" icon="calendar-days" color="purple" :hint="'Quota '.(float) $row['type']->annual_quota.' j'" />@endforeach
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="panel p-4 xl:col-span-2 space-y-3">
            <x-filter-bar class="border-0 p-0 shadow-none" placeholder="Employé, matricule, n°…" :statuses="$statuses">
                <flux:select wire:model.live="leaveTypeFilter" size="sm" class="max-w-48"><flux:select.option value="">Tous les types</flux:select.option>@foreach ($types as $t)<flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>@endforeach</flux:select>
            </x-filter-bar>
            <flux:table>
                <flux:table.columns><flux:table.column>N°</flux:table.column><flux:table.column>Employé</flux:table.column><flux:table.column>Type</flux:table.column><flux:table.column>Période</flux:table.column><flux:table.column align="end">Jours</flux:table.column><flux:table.column>Statut</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @forelse ($requests as $request)
                        <flux:table.row :key="$request->id">
                            <flux:table.cell class="text-xs">{{ $request->number }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $request->employee->fullName() }}<div class="text-xs text-zinc-500">{{ $request->employee->department?->name }}</div></flux:table.cell>
                            <flux:table.cell>{{ $request->leaveType->name }}</flux:table.cell>
                            <flux:table.cell>{{ $request->starts_on->format('d/m') }} → {{ $request->ends_on->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell align="end">{{ (float) $request->days }}</flux:table.cell>
                            <flux:table.cell><x-status-badge :status="$request->status" />@if ($request->rejection_reason)<div class="text-xs text-red-600">{{ $request->rejection_reason }}</div>@endif</flux:table.cell>
                            <flux:table.cell>
                                @if (in_array($request->status->value, ['pending', 'manager_approved']) && $request->employee->user_id !== auth()->id())
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="xs" variant="primary" icon="check" wire:click="approve({{ $request->id }})">{{ $request->status->value === 'pending' ? 'Resp.' : 'RH' }}</flux:button>
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="openReject({{ $request->id }})" />
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="calendar-days" title="Aucune demande" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="panel p-4">
            <flux:heading>Calendrier des absences validées</flux:heading>
            <ul class="mt-2 space-y-2 text-sm">
                @forelse ($calendar as $leave)
                    <li class="flex items-center justify-between rounded-lg bg-purple-50 p-2 dark:bg-purple-500/10" wire:key="cal-{{ $leave->id }}"><span>{{ $leave->employee->fullName() }} <span class="text-xs text-zinc-500">· {{ $leave->leaveType->name }}</span></span><span class="text-xs">{{ $leave->starts_on->format('d/m') }}–{{ $leave->ends_on->format('d/m') }}</span></li>
                @empty
                    <li class="text-zinc-500">Aucune absence planifiée.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <flux:modal name="leave" class="w-full max-w-lg">
        <form wire:submit="submit" class="space-y-4">
            <flux:heading size="lg">Demande de congé</flux:heading>
            <flux:select wire:model="form.employee_id" label="Employé"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($employees as $e)<flux:select.option :value="$e->id">{{ $e->first_name }} {{ $e->last_name }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="form.leave_type_id" label="Type"><flux:select.option value="">Choisir…</flux:select.option>@foreach ($types as $t)<flux:select.option :value="$t->id">{{ $t->name }}{{ $t->requires_document ? ' (justificatif requis)' : '' }}</flux:select.option>@endforeach</flux:select>
            <div class="grid grid-cols-2 gap-3"><flux:input wire:model="form.starts_on" type="date" label="Du" /><flux:input wire:model="form.ends_on" type="date" label="Au" /></div>
            <flux:textarea wire:model="form.reason" label="Motif" rows="2" />
            <flux:input type="file" wire:model="document" label="Justificatif" />
            <flux:button type="submit" variant="primary" class="w-full">Envoyer</flux:button>
        </form>
    </flux:modal>

    <flux:modal name="reject" class="w-full max-w-md">
        <form wire:submit="reject" class="space-y-4">
            <flux:heading size="lg">Refuser la demande</flux:heading>
            <flux:input wire:model="rejectionReason" label="Motif (obligatoire)" />
            <flux:button type="submit" variant="danger" class="w-full">Refuser</flux:button>
        </form>
    </flux:modal>
</div>
