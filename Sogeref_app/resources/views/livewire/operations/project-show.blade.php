<div>
    @php
        $p = $project;
        $canManage = auth()->user()->canManage('projects');
        $estimated = $p->tasks->sum('estimated_hours');
    @endphp
    <x-ui.page-header :title="$p->name" :eyebrow="$p->code.' · Projet'" :back="route('projects')">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-fg-muted">
                <span>{{ $p->contact?->display_name ?? 'Projet interne' }}</span><span>· Chef de projet : {{ $p->manager?->name ?? '—' }}</span>
                @if ($p->salesOrder)<a href="{{ route('orders.show', $p->salesOrder) }}" wire:navigate class="hover:text-fg">· {{ $p->salesOrder->number }}</a>@endif
                @if ($p->site)<a href="{{ route('sites.show', $p->site) }}" wire:navigate class="hover:text-fg">· Chantier {{ $p->site->code }}</a>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            @if ($canManage)
                <select wire:model.live="status" class="input w-40">@foreach (config('erp.statuses.project') as $k => [$l])<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <x-ui.button variant="accent" icon="plus" wire:click="editTask">Tâche</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Avancement" :value="$p->computedProgress().' %'" icon="chart-pie" tone="sun" />
        <x-ui.stat label="Tâches terminées" :value="$p->tasks->where('status', 'done')->count().' / '.$p->tasks->count()" icon="check-circle" tone="emerald" />
        <x-ui.stat label="Heures saisies" :value="qty($hours->sum('hours')).' h'" icon="clock" tone="lagoon" :hint="'sur '.qty($estimated).' h estimées'" />
        <x-ui.stat label="Budget" :value="money($p->budget, 'USD', 0)" icon="banknotes" :hint="$p->end_date ? 'échéance '.$p->end_date->format('d/m/Y') : null" />
    </div>

    <div class="kanban" x-data="kanban('move')">
        @foreach (config('erp.statuses.task') as $status => [$label, $tone])
            @php $tasks = $columns->get($status, collect())->sortBy('position'); @endphp
            <section class="kanban-col">
                <header class="flex items-center justify-between px-2 pt-1 pb-2.5">
                    <x-ui.badge :tone="$tone" dot>{{ $label }}</x-ui.badge>
                    @if ($canManage)<button type="button" wire:click="editTask(null, '{{ $status }}')" class="btn btn-ghost btn-sm btn-icon" aria-label="Ajouter"><x-ui.icon name="plus" class="size-4" /></button>@endif
                </header>
                <div class="flex min-h-40 flex-col gap-2" data-column="{{ $status }}">
                    @foreach ($tasks as $t)
                        <article class="kanban-card" data-id="{{ $t->id }}" wire:key="t-{{ $t->id }}">
                            <button type="button" wire:click="editTask({{ $t->id }})" class="block w-full text-left">
                                <p @class(['text-[13.5px] leading-snug font-semibold', 'line-through text-fg-subtle' => $status === 'done'])>{{ $t->title }}</p>
                                <div class="mt-2.5 flex items-center justify-between text-[11.5px] text-fg-subtle">
                                    <span class="flex items-center gap-1.5">@if (in_array($t->priority, ['haute', 'urgente']))<x-ui.status map="priority" :value="$t->priority" />@endif @if ($t->due_date)<span @class(['text-flame-600 font-semibold' => $t->due_date->lt(today()) && $status !== 'done'])>{{ $t->due_date->format('d/m') }}</span>@endif</span>
                                    @if ($t->assignee)<x-ui.avatar :name="$t->assignee->name" size="xs" />@endif
                                </div>
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    @if ($hoursByUser->isNotEmpty())
        <x-ui.card title="Temps passé par intervenant" icon="clock" class="mt-6">
            <ul class="space-y-3">
                @foreach ($hoursByUser as $name => $h)
                    <li><div class="mb-1 flex justify-between text-[13px]"><span class="font-medium">{{ $name }}</span><span class="font-semibold tabular-nums">{{ qty($h) }} h</span></div><x-ui.progress :value="round($h / max(1, $hoursByUser->max()) * 100)" tone="lagoon" /></li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <x-ui.drawer wire:model="showTask" :title="$editing ? 'Modifier la tâche' : 'Nouvelle tâche'" icon="check-circle">
        <div class="space-y-4">
            <x-ui.input label="Titre" wire:model="task.title" required />
            <x-ui.textarea label="Description" wire:model="task.description" rows="3" />
            <div class="grid grid-cols-2 gap-3">
                <x-ui.select label="Statut" wire:model="task.status" :options="collect(config('erp.statuses.task'))->map(fn ($s) => $s[0])" />
                <x-ui.select label="Priorité" wire:model="task.priority" :options="collect(config('erp.statuses.priority'))->map(fn ($s) => $s[0])" />
                <x-ui.select label="Assignée à" wire:model="task.assigned_to" placeholder="—" :options="$users->pluck('name', 'id')" />
                <x-ui.input label="Échéance" type="date" wire:model="task.due_date" />
                <x-ui.input label="Heures estimées" type="number" step="0.5" wire:model="task.estimated_hours" />
            </div>
        </div>
        <x-slot:footer>
            @if ($editing)<x-ui.button variant="ghost" icon="trash" class="mr-auto text-flame-600" wire:click="deleteTask" wire:confirm="Supprimer cette tâche ?">Supprimer</x-ui.button>@endif
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveTask">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
