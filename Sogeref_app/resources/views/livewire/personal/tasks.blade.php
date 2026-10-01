<div>
    @php
        $isToday = $day->isToday();
        $progress = $tasks->count() ? (int) round($done / $tasks->count() * 100) : 0;
        $priorityDot = ['basse' => 'bg-ink-300', 'normale' => 'bg-lagoon-400', 'haute' => 'bg-sun-400', 'urgente' => 'bg-flame-500'];
    @endphp

    <x-ui.page-header title="Mes tâches du jour" eyebrow="Espace personnel" subtitle="Votre liste de choses à faire, jour par jour : priorités, créneaux horaires, report des tâches non terminées.">
        <x-slot:actions>
            @if ($overdue)
                <x-ui.button variant="secondary" icon="arrow-uturn-right" wire:click="carryOver">Reporter {{ $overdue }} tâche(s) à aujourd’hui</x-ui.button>
            @endif
            <x-ui.button variant="secondary" icon="printer" :href="route('personal.tasks.print', ['jour' => $day->toDateString()])" :navigate="false" target="_blank">Imprimer</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>


    {{-- ═══ NAVIGATION PAR DATE ═════════════════════════════════════════════ --}}
    <div class="card mb-5 p-3 sm:p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1">
                <x-ui.button variant="ghost" size="sm" square icon="chevron-left" wire:click="shiftDay(-1)" aria-label="Jour précédent" />
                <div class="min-w-48 text-center">
                    <p class="font-display text-[18px] font-semibold">{{ ucfirst($day->translatedFormat('l j F')) }}</p>
                    <p class="text-[12px] text-fg-subtle">{{ $isToday ? 'Aujourd’hui' : ($day->isTomorrow() ? 'Demain' : ($day->isYesterday() ? 'Hier' : $day->format('d/m/Y'))) }}</p>
                </div>
                <x-ui.button variant="ghost" size="sm" square icon="chevron-right" wire:click="shiftDay(1)" aria-label="Jour suivant" />
            </div>
            <div class="flex items-center gap-2">
                @unless ($isToday)<x-ui.button variant="soft" size="sm" wire:click="goToday">Aujourd’hui</x-ui.button>@endunless
                <input type="date" wire:model.live="date" class="input h-8 w-auto text-[13px]" aria-label="Choisir une date">
            </div>
        </div>
        <div class="mt-3 grid grid-cols-7 gap-1.5">
            @foreach ($week as $d)
                @php $count = $weekCounts[$d->toDateString()] ?? null; @endphp
                <button type="button" wire:click="$set('date', '{{ $d->toDateString() }}')" @class([
                    'flex flex-col items-center rounded-xl border px-1 py-2 transition',
                    'border-brand-600 bg-brand-600 text-white' => $d->isSameDay($day),
                    'border-line hover:border-brand-200 hover:bg-surface-2' => ! $d->isSameDay($day),
                ])>
                    <span @class(['text-[10.5px] font-semibold uppercase', 'text-white/70' => $d->isSameDay($day), 'text-fg-subtle' => ! $d->isSameDay($day), 'text-sun-600' => $d->isToday() && ! $d->isSameDay($day)])>{{ $d->translatedFormat('D') }}</span>
                    <span class="font-display text-[16px] font-semibold">{{ $d->format('j') }}</span>
                    <span @class(['mt-0.5 h-4 text-[10.5px] font-semibold', 'text-white/80' => $d->isSameDay($day), 'text-fg-subtle' => ! $d->isSameDay($day)])>{{ $count ? (int) $count->done.'/'.$count->total : '' }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-3">
        <x-ui.card class="xl:col-span-2" :title="$tasks->count().' tâche(s)'" :subtitle="$tasks->count() ? $done.' terminée(s) · '.$progress.' %' : 'Rien de prévu pour ce jour'" icon="check-circle" :padded="false">
            <x-slot:actions>
                @if ($done)<x-ui.button variant="ghost" size="sm" icon="trash" wire:click="clearDone" wire:confirm="Retirer les tâches terminées de ce jour ?">Retirer les terminées</x-ui.button>@endif
            </x-slot:actions>
            @if ($tasks->count())
                <div class="h-1 bg-ink-100 dark:bg-white/6"><div class="h-1 bg-emerald-500 transition-all" style="width: {{ $progress }}%"></div></div>
            @endif

            {{-- Ajout rapide --}}
            <form wire:submit="add" class="grid grid-cols-2 gap-2 border-b border-line bg-surface-2 p-3 sm:grid-cols-[minmax(0,1fr)_auto_auto_auto_auto]">
                <input type="text" wire:model="title" @class(['input col-span-2 sm:col-span-1', 'input-error' => $errors->has('title')]) placeholder="Nouvelle tâche… (Entrée pour ajouter)" maxlength="255" aria-label="Nouvelle tâche">
                <select wire:model="priority" class="input col-span-2 sm:col-span-1 sm:w-32" aria-label="Priorité">
                    @foreach (\App\Models\PersonalTask::PRIORITIES as $p)<option value="{{ $p }}">{{ erp_status('priority', $p)[0] }}</option>@endforeach
                </select>
                <input type="time" wire:model="startTime" class="input sm:w-28" aria-label="Heure de début" title="Début">
                <input type="time" wire:model="endTime" class="input sm:w-28" aria-label="Heure de fin" title="Fin">
                <x-ui.button type="submit" variant="primary" icon="plus" class="col-span-2 sm:col-span-1">Ajouter</x-ui.button>
            </form>
            @if ($errors->hasAny(['title', 'startTime', 'endTime']))
                <p class="field-error px-4 pt-2">{{ $errors->first('title') ?: ($errors->first('startTime') ?: $errors->first('endTime')) }}</p>
            @endif

            <ul class="divide-y divide-line" x-data="sortableList('reorder')">
                @forelse ($tasks as $task)
                    <li wire:key="pt-{{ $task->id }}" data-id="{{ $task->id }}" @class(['group flex items-start gap-3 px-3 py-3 sm:px-4', 'bg-surface-2/60' => $task->is_done])>
                        <span data-handle class="mt-0.5 cursor-grab text-fg-subtle opacity-40 transition group-hover:opacity-100 active:cursor-grabbing" title="Glisser pour réordonner"><x-ui.icon name="bars-2" class="size-4" /></span>
                        <button type="button" wire:click="toggle({{ $task->id }})" @class(['mt-0.5 grid size-5 shrink-0 place-items-center rounded-md border-2 transition', 'border-emerald-500 bg-emerald-500 text-white' => $task->is_done, 'border-line-strong hover:border-brand-400' => ! $task->is_done]) aria-label="{{ $task->is_done ? 'Marquer à faire' : 'Marquer terminée' }}">
                            @if ($task->is_done)<x-ui.icon name="check" class="size-3.5" />@endif
                        </button>
                        <div class="min-w-0 flex-1">
                            @if ($editingId === $task->id)
                                <form wire:submit="saveEdit" class="space-y-2">
                                    <input type="text" wire:model="editTitle" class="input" maxlength="255" aria-label="Intitulé" autofocus>
                                    <textarea wire:model="editNotes" rows="2" class="input" placeholder="Notes (facultatif)" aria-label="Notes"></textarea>
                                    <div class="flex gap-2"><x-ui.button type="submit" size="sm" variant="primary" icon="check">Enregistrer</x-ui.button><x-ui.button size="sm" variant="ghost" wire:click="$set('editingId', null)">Annuler</x-ui.button></div>
                                </form>
                            @else
                                <p @class(['font-medium', 'text-fg-subtle line-through' => $task->is_done]) wire:dblclick="edit({{ $task->id }})">{{ $task->title }}</p>
                                @if ($task->notes)<p class="mt-0.5 text-[12.5px] whitespace-pre-line text-fg-subtle">{{ $task->notes }}</p>@endif
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-[12px] text-fg-subtle">
                                    @if ($task->timeSlot())<span class="inline-flex items-center gap-1 tabular-nums"><x-ui.icon name="clock" class="size-3.5" />{{ $task->timeSlot() }}</span>@endif
                                    @if ($task->carried_from)<span class="inline-flex items-center gap-1 text-sun-700 dark:text-sun-300"><x-ui.icon name="arrow-uturn-right" class="size-3.5" />Reportée du {{ $task->carried_from->format('d/m') }}</span>@endif
                                    @if ($task->is_done && $task->done_at)<span>Terminée à {{ $task->done_at->format('H:i') }}</span>@endif
                                </div>
                            @endif
                        </div>
                        <div x-data="{ menu: false }" class="relative shrink-0">
                            <button type="button" x-on:click="menu = !menu" class="inline-flex items-center gap-1.5 rounded-full px-1 py-1" aria-label="Changer la priorité">
                                <span class="size-2 rounded-full sm:hidden {{ $priorityDot[$task->priority] ?? 'bg-ink-300' }}"></span>
                                <span class="hidden sm:inline"><x-ui.status map="priority" :value="$task->priority" /></span>
                            </button>
                            <div x-show="menu" x-cloak x-on:click.outside="menu = false" x-transition class="absolute right-0 z-20 mt-1 w-36 rounded-xl border border-line bg-surface p-1 shadow-[var(--shadow-pop)]">
                                @foreach (\App\Models\PersonalTask::PRIORITIES as $p)
                                    <button type="button" wire:click="setPriority({{ $task->id }}, '{{ $p }}')" x-on:click="menu = false" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-[13px] hover:bg-surface-2"><span class="size-2 rounded-full {{ $priorityDot[$p] }}"></span>{{ erp_status('priority', $p)[0] }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                            <button type="button" wire:click="edit({{ $task->id }})" class="btn btn-ghost btn-sm btn-icon" aria-label="Modifier"><x-ui.icon name="pencil-square" class="size-4" /></button>
                            <button type="button" wire:click="delete({{ $task->id }})" wire:confirm="Supprimer cette tâche ?" class="btn btn-ghost btn-sm btn-icon hover:text-flame-600" aria-label="Supprimer"><x-ui.icon name="trash" class="size-4" /></button>
                        </div>
                    </li>
                @empty
                    <li><x-ui.empty icon="check-circle" title="Aucune tâche pour ce jour" text="Ajoutez une tâche ci-dessus : intitulé, priorité et créneau horaire facultatif." /></li>
                @endforelse
            </ul>
        </x-ui.card>

        <div class="space-y-5">
            <x-ui.card title="Bilan du jour" icon="chart-bar-square">
                <div class="flex items-center gap-4">
                    <div class="relative grid size-20 shrink-0 place-items-center rounded-full" style="background: conic-gradient(var(--color-emerald-500) {{ $progress * 3.6 }}deg, var(--ui-line) 0)">
                        <span class="grid size-16 place-items-center rounded-full bg-surface font-display text-[18px] font-semibold">{{ $progress }}%</span>
                    </div>
                    <dl class="grid flex-1 grid-cols-2 gap-2 text-[13px]">
                        <div><dt class="text-fg-subtle">À faire</dt><dd class="font-display text-[20px] font-semibold">{{ $tasks->count() - $done }}</dd></div>
                        <div><dt class="text-fg-subtle">Terminées</dt><dd class="font-display text-[20px] font-semibold text-emerald-600">{{ $done }}</dd></div>
                        <div><dt class="text-fg-subtle">Urgentes</dt><dd class="font-semibold text-flame-600">{{ $tasks->where('priority', 'urgente')->where('is_done', false)->count() }}</dd></div>
                        <div><dt class="text-fg-subtle">En retard</dt><dd class="font-semibold text-sun-700">{{ $overdue }}</dd></div>
                    </dl>
                </div>
            </x-ui.card>
            <x-ui.card title="Astuces" icon="information-circle">
                <ul class="space-y-2 text-[13px] text-fg-muted">
                    <li class="flex gap-2"><x-ui.icon name="bars-2" class="size-4 text-fg-subtle" />Glissez une tâche pour la réordonner.</li>
                    <li class="flex gap-2"><x-ui.icon name="pencil-square" class="size-4 text-fg-subtle" />Double-cliquez un intitulé pour le modifier et ajouter des notes.</li>
                    <li class="flex gap-2"><x-ui.icon name="arrow-uturn-right" class="size-4 text-fg-subtle" />Les tâches non terminées des jours passés peuvent être reportées à aujourd’hui en un clic.</li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</div>
