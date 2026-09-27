<div class="space-y-6">
    <x-page-header title="Tâches & rappels" subtitle="Glissez les cartes pour changer leur statut.">
        <x-slot:actions>
            <flux:radio.group wire:model.live="scope" variant="segmented" size="sm">
                <flux:radio value="mine" label="Assignées" /><flux:radio value="created" label="Créées" /><flux:radio value="all" label="Toutes" />
            </flux:radio.group>
            @can('tasks.create')<flux:modal.trigger name="task"><flux:button size="sm" variant="primary" icon="plus">Tâche</flux:button></flux:modal.trigger>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($columns as $status => $tasks)
            <div wire:key="tc-{{ $status }}">
                <flux:heading size="sm" class="mb-2">{{ \App\Enums\TaskStatus::from($status)->label() }} · {{ $tasks->count() }}</flux:heading>
                <div class="min-h-40 space-y-2 rounded-xl bg-zinc-100 p-2 dark:bg-zinc-800/50" wire:sort="move" wire:sort:group="tasks" wire:sort:group-id="{{ $status }}">
                    @foreach ($tasks as $task)
                        <div wire:sort:item="{{ $task->id }}" wire:key="task-{{ $task->id }}" class="panel cursor-grab p-3 text-sm">
                            <div class="flex items-start justify-between gap-2"><span class="font-medium">{{ $task->title }}</span><flux:badge size="sm" :color="$task->priority->color()">{{ $task->priority->label() }}</flux:badge></div>
                            @if ($task->description)<div class="mt-1 line-clamp-2 text-zinc-500">{{ $task->description }}</div>@endif
                            <div class="mt-2 flex justify-between text-xs text-zinc-500"><span>{{ $task->assignee?->name ?? '—' }}</span><span @class(['text-red-600 font-medium' => $task->due_at?->isPast() && $task->status->value !== 'done'])>{{ $task->due_at?->format('d/m H:i') }}</span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="task" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Nouvelle tâche</flux:heading>
            <flux:input wire:model="form.title" label="Titre" required />
            <flux:textarea wire:model="form.description" label="Description" rows="2" />
            <div class="grid grid-cols-2 gap-3">
                <flux:select wire:model="form.assigned_to" label="Assignée à"><flux:select.option value="">—</flux:select.option>@foreach ($users as $u)<flux:select.option :value="$u->id">{{ $u->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="form.priority" label="Priorité">@foreach ($priorities as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="form.due_at" type="datetime-local" label="Échéance" />
                <flux:input wire:model="form.remind_at" type="datetime-local" label="Rappel" />
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Créer</flux:button>
        </form>
    </flux:modal>
</div>
