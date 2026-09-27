<div class="space-y-6">
    <x-page-header title="Départements & mentors" subtitle="Responsables, mentors, effectifs, présence, contrats et activité opérationnelle.">
        <x-slot:actions>@can('departments.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="edit">Département</flux:button>@endcan</x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-4">
        <div class="space-y-2">
            @foreach ($departments as $department)
                <button wire:click="$set('selectedId', {{ $department->id }})" wire:key="d-{{ $department->id }}" @class(['panel flex w-full items-center justify-between p-3 text-left text-sm', 'ring-2 ring-indigo-500' => $selectedId === $department->id])>
                    <div><div class="font-medium">{{ $department->name }}</div><div class="text-xs text-zinc-500">{{ $department->manager?->fullName() ?? 'Sans responsable' }}</div></div>
                    <flux:badge size="sm">{{ $department->employees_count }}</flux:badge>
                </button>
            @endforeach
        </div>

        <div class="space-y-6 xl:col-span-3">
            @if ($selected)
                <div class="panel p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:heading size="lg">{{ $selected->name }} <span class="text-sm text-zinc-500">({{ $selected->code }})</span></flux:heading>
                            <flux:text>Responsable : {{ $selected->manager?->fullName() ?? '—' }} · Mentor : {{ $selected->mentor?->fullName() ?? '—' }}</flux:text>
                        </div>
                        @can('departments.update')<flux:button size="sm" icon="pencil" wire:click="edit({{ $selected->id }})">Modifier</flux:button>@endcan
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <x-kpi label="Effectif" :value="$stats['headcount']" icon="users" />
                        <x-kpi label="Présents aujourd’hui" :value="$stats['present']" icon="check-circle" color="emerald" />
                        <x-kpi label="Absents" :value="$stats['absent']" icon="x-circle" color="red" />
                        <x-kpi label="En congé" :value="$stats['on_leave']" icon="calendar-days" color="purple" />
                        <x-kpi label="Heures travaillées (mois)" :value="$stats['hours_month'].' h'" icon="clock" color="sky" />
                        <x-kpi label="Contrats à échéance (60 j)" :value="$stats['contracts_ending']" icon="document-text" color="amber" />
                        <x-kpi label="Tâches ouvertes" :value="$stats['tasks_open']" icon="clipboard-document-list" color="zinc" />
                        <x-kpi label="Tâches terminées (mois)" :value="$stats['tasks_done_month']" icon="clipboard-document-check" color="emerald" />
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">@foreach ($stats['by_status'] as $label => $count)<flux:badge>{{ $label }} : {{ $count }}</flux:badge>@endforeach</div>
                    @if ($selected->objectives)<flux:callout class="mt-4" icon="flag" heading="Objectifs" :text="$selected->objectives" />@endif
                    @if ($selected->activities)<flux:callout class="mt-2" icon="bolt" heading="Activités" :text="$selected->activities" />@endif
                </div>
                <div class="panel p-4">
                    <flux:heading>Membres</flux:heading>
                    <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($members as $member)
                            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700" wire:key="mb-{{ $member->id }}">
                                <flux:avatar size="sm" :initials="mb_substr($member->first_name, 0, 1).mb_substr($member->last_name, 0, 1)" />
                                <div class="min-w-0 text-sm"><div class="truncate font-medium">{{ $member->fullName() }}</div><div class="truncate text-xs text-zinc-500">{{ $member->job_title }} · {{ $member->employment_status->label() }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="panel"><x-empty-state icon="rectangle-group" title="Aucun département" /></div>
            @endif
        </div>
    </div>

    <flux:modal name="department" class="w-full max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Département</flux:heading>
            <div class="grid grid-cols-3 gap-3">
                <flux:input wire:model="form.code" label="Code" />
                <flux:input wire:model="form.name" label="Nom" class="col-span-2" />
                <flux:select wire:model="form.site_id" label="Siège"><flux:select.option value="">—</flux:select.option>@foreach (\App\Models\Site::query()->whereIn('id', auth()->user()->accessibleSiteIds())->get() as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.manager_id" label="Responsable"><flux:select.option value="">—</flux:select.option>@foreach ($employees as $e)<flux:select.option :value="$e->id">{{ $e->first_name }} {{ $e->last_name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.mentor_id" label="Mentor"><flux:select.option value="">—</flux:select.option>@foreach ($employees as $e)<flux:select.option :value="$e->id">{{ $e->first_name }} {{ $e->last_name }}</flux:select.option>@endforeach</flux:select>
            </div>
            <flux:textarea wire:model="form.objectives" label="Objectifs" rows="2" />
            <flux:textarea wire:model="form.activities" label="Activités" rows="2" />
            <flux:button type="submit" variant="primary" class="w-full">Enregistrer</flux:button>
        </form>
    </flux:modal>
</div>
